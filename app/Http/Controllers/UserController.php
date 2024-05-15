<?php

namespace App\Http\Controllers;

use App\Enums\ImageType;
use App\Events\UserCreated;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\SNS\SNSPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserController
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return new JsonResponse(User::with(['profileImage'])->get()->all());
    }

    /**
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
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

    /**
     * @param StoreImageRequest $request
     * @param User $user
     * @param SNSPublisher $snsPublisher
     * @return JsonResponse
     */
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

        if (false === $path) {
            return new JsonResponse(['error' => 'Unable to store image'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user->profileImage()->create(['url' => $path, 'type' => ImageType::PROFILE->value]);

        return new JsonResponse($user->load('profileImage'), Response::HTTP_CREATED);
    }

    /**
     * @param StoreScheduleRequest $request
     * @param User $user
     * @param SNSPublisher $snsPublisher
     * @return JsonResponse
     * @throws \Exception
     */
    public function storeSchedule(StoreScheduleRequest $request, User $user, SNSPublisher $snsPublisher): JsonResponse
    {
        $userKey = $user->getKey();
        $file = $request->file('file');

        $path = Storage::disk('s3')->putFile(sprintf('users/%s/schedule', $userKey), $file);

        if (false === $path) {
            return new JsonResponse(['error' => 'Unable to store schedule file'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $snsPublisher->publish(
                json_encode(['UserID' => $userKey, 'FileName' => basename($path)]),
                [
                    'MessageType' => [
                        'DataType' => 'String',
                        'StringValue' => 'ScheduleLoaded',
                    ]
                ],
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
            Log::error($message);

            return new JsonResponse(['error' => $message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['loaded' => $path], Response::HTTP_CREATED);
    }
}
