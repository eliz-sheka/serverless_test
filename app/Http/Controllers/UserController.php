<?php

namespace App\Http\Controllers;

use App\Enums\ImageType;
use App\Events\UserCreated;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\CSVReader;
use AWS\CRT\Log;
use Aws\Sns\SnsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
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

    public function storeImage(StoreImageRequest $request, User $user): JsonResponse
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
        }

        return new JsonResponse($user->load('profileImage'), Response::HTTP_CREATED);
    }

    public function storeSchedule(StoreScheduleRequest $request, User $user): JsonResponse
    {
        $userKey = $user->getKey();
        $file = $request->file('file');

        $path = Storage::disk('s3')->putFile(sprintf('users/%s/schedule', $userKey), $file);

        if (false === $path) {
            Log::log(Log::INFO, 'Unable to store schedule file');
        }

        $config = Config::get('services.sns');

        $snsClient = new SnsClient([
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);

        $result = $snsClient->publish([
            'TopicArn' => $config['arn'],
            'Message' => json_encode([
                'UserID' => $userKey,
                'Type' => 'ScheduleLoaded'
            ]),
        ]);

        return new JsonResponse($config + ['loaded' => $path] + $result->toArray(), Response::HTTP_CREATED);
    }
}
