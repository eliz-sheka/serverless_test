<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Listeners\UserCreatedActions;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SnsClient::class, function ($app) {
            $config = $app['config']['services']['sns'];

            return new SnsClient([
                'version' => 'latest',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                    'token' => $config['token'],
                ],
            ]);
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
