<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Services\SNS\SNSPublisher;
use Illuminate\Support\Facades\Config;

class UserCreatedActions
{
    public function __construct(private SNSPublisher $snsPublisher)
    {

    }

    public function handle(UserCreated $event): void
    {
        $config = Config::get('services.sns');

        $this->snsPublisher->publish(
            $config['arn'],
            json_encode(['UserID' => $event->userId]),
            [
                'MessageType' => [
                    'DataType' => 'String',
                    'StringValue' => 'UserCreated',
                ]
            ],
        );

        // TODO: Check if status != 200
    }
}
