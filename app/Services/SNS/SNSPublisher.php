<?php

namespace App\Services\SNS;

use Aws\Result;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Log;

class SNSPublisher
{
    /**
     * @param SnsClient $client
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
        $this->checkTopic($topic);

        try {
            return $this->client->publish([
                'TopicArn' => $this->topic,
                'Message' => $message,
                'MessageAttributes' => $attributes,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            throw $e;
        }
    }

    /**
     * @param string|null $topic
     * @return void
     * @throws \Exception
     */
    private function checkTopic(?string $topic): void
    {
        if (!empty($topic)) {
            $this->topic = $topic;
        }
    }
}
