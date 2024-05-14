<?php

namespace App\Services\SNS;

use Aws\Result;
use Aws\Sns\SnsClient;

class SNSPublisher
{
    public function __construct(private SnsClient $client)
    {
    }

    public function publish(string $topic, string $message, array $attributes = []): Result
    {
        return $this->client->publish([
            'TopicArn' => $topic,
            'Message' => $message,
            'MessageAttributes' => $attributes,
        ]);
    }
}
