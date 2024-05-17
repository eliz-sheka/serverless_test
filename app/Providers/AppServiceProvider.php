<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Listeners\UserCreatedActions;
use App\Services\SNS\SNSPublisher;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SNSPublisher::class, function ($app) {
            $key = $app->config->get('services.sns.key');
            $secret = $app->config->get('services.sns.secret');
            $token = $app->config->get('services.sns.token');
            $region = $app->config->get('services.sns.region');
            $topicArn = $app->config->get('services.sns.arn');

            if (!$key || !$secret || !$region || !$topicArn || !$token) {
                throw new RuntimeException('Missing SNS config!');
            }

            return new SNSPublisher(
                new SnsClient([
                    'version' => 'latest',
                    'region' => $region,
                    'credentials' => [
                        'key' => $key,
                        'secret' => $secret,
                        'token' => $token,
                    ],
                ]),
                $topicArn
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            UserCreated::class,
            UserCreatedActions::class,
        );
    }
}
