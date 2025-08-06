<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AMQP Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for RabbitMQ/AMQP connection
    |
    */

    'host' => env('AMQP_HOST', 'localhost'),
    'port' => env('AMQP_PORT', 5672),
    'user' => env('AMQP_USER', 'guest'),
    'password' => env('AMQP_PASSWORD', 'guest'),
    'vhost' => env('AMQP_VHOST', '/'),

];