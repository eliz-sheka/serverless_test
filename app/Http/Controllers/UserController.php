<?php

namespace App\Http\Controllers;

use App\Enums\ImageType;
use App\Events\UserCreated;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\SNS\SNSPublisher;
use Aws\Exception\AwsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController
{
    public function index(): JsonResponse
    {
        return new JsonResponse(User::with(['profileImage'])->get()->all());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = User::query()->create([
            'email' => $request->validated('email'),
            'password' => bcrypt($request->validated('password')),
        ]);

        $userKey = $user->getKey();
        event(new UserCreated($userKey));

        return new JsonResponse([
            'id' => $userKey,
            'email' => $user->email,
        ], Response::HTTP_CREATED);
    }

    public function storeImage(StoreImageRequest $request, User $user, SNSPublisher $snsPublisher): JsonResponse
    {
        if ($profileImage = $user->profileImage) {
            if (Storage::disk('s3')->delete($profileImage->url)) {
                $profileImage->delete();
            }
        }

        $userKey = $user->getKey();
        $image = $request->file('image');
        $path = Storage::disk('s3')->putFile(sprintf('users/%s/profile', $userKey), $image);

        if ($path) {
            $user->profileImage()->create(['url' => $path, 'type' => ImageType::PROFILE->value]);

            $config = Config::get('services.sns');

            $snsPublisher->publish(
                $config['arn'],
                json_encode(['UserID' => $userKey]),
                [
                    'MessageType' => [
                        'DataType' => 'String',
                        'StringValue' => 'ProfileImageLoaded',
                    ]
                ],
            );
        }

        return new JsonResponse($user->load('profileImage'), Response::HTTP_CREATED);
    }

    public function storeSchedule(StoreScheduleRequest $request, User $user, SNSPublisher $snsPublisher): JsonResponse
    {
        $userKey = $user->getKey();
        $file = $request->file('file');

        $path = Storage::disk('s3')->putFile(sprintf('users/%s/schedule', $userKey), $file);

        if (false === $path) {
            Log::info('Unable to store schedule file');
        }

        $config = Config::get('services.sns');

        try {
            $result = $snsPublisher->publish(
                $config['arn'],
                json_encode(['UserID' => $userKey, 'FileName' => basename($path)]),
                [
                    'MessageType' => [
                        'DataType' => 'String',
                        'StringValue' => 'ScheduleLoaded',
                    ]
                ],
            );
        } catch (AwsException $e) {
            // output error message if fails
            Log::error($e->getMessage());
        }

        return new JsonResponse(['loaded' => $path, 'snsData' => $result->toArray()], Response::HTTP_CREATED);
    }
}
