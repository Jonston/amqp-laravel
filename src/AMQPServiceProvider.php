<?php

namespace Jonston\AmqpLaravel;

use Illuminate\Support\ServiceProvider;

class AMQPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AMQPService::class, function ($app) {
            return new AMQPService();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/amqp.php' => config_path('amqp.php'),
        ], 'amqp-config');

        $this->mergeConfigFrom(
            __DIR__.'/../config/amqp.php', 'amqp'
        );
    }
}