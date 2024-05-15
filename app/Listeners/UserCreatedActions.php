<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Services\SNS\SNSPublisher;
use Illuminate\Support\Facades\Log;

class UserCreatedActions
{
    /**
     * @param SNSPublisher $snsPublisher
     */
    public function __construct(private SNSPublisher $snsPublisher)
    {

    }

    /**
     * @param UserCreated $event
     * @return void
     */
    public function handle(UserCreated $event): void
    {
        try {
            $this->snsPublisher->publish(
                json_encode(['UserID' => $event->userId]),
                [
                    'MessageType' => [
                        'DataType' => 'String',
                        'StringValue' => 'UserCreated',
                    ]
                ],
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
