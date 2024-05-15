<?php

namespace App\Services\SNS;

use Aws\Result;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Config;

class SNSPublisher
{
    protected ?string $topic = null;

    /**
     * @param SnsClient $client
     */
    public function __construct(private SnsClient $client)
    {
        $this->topic = Config::get('services.sns.arn');
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

        return $this->client->publish([
            'TopicArn' => $this->topic,
            'Message' => $message,
            'MessageAttributes' => $attributes,
        ]);
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
        } elseif (empty($this->topic)) {
            throw new \Exception('Topic is required');
        }
    }
}
