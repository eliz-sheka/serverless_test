<?php

namespace App\Services\SNS;

use Aws\Result;
use Aws\Sns\SnsClient;

class SNSPublisher
{
    /**
     * @param SnsClient $client
     * @param string $topic
     */
    public function __construct(private SnsClient $client, private string $topic)
    {

    }

    /**
     * @param string $message
     * @param array $attributes
     * @param string|null $topic
     * @return Result
     * @throws \Exception
     */
    public function publish(string $message, array $attributes = [], ?string $topic = null): Result
    {
        return $this->client->publish([
            'TopicArn' => $topic ?? $this->topic,
            'Message' => $message,
            'MessageAttributes' => $attributes,
        ]);
    }
}
