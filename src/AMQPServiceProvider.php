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
}