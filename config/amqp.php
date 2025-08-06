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

    /*
    |--------------------------------------------------------------------------
    | Exchanges
    |--------------------------------------------------------------------------
    |
    | Define exchanges that will be created during setup
    | Uncomment and configure as needed
    |
    */

    // 'exchanges' => [
    //     'orders' => [
    //         'type' => 'fanout',
    //         'durable' => true,
    //         'auto_delete' => false,
    //     ],
    //     'notifications' => [
    //         'type' => 'direct',
    //         'durable' => true,
    //         'auto_delete' => false,
    //     ],
    // ],

    /*
    |--------------------------------------------------------------------------
    | Queues
    |--------------------------------------------------------------------------
    |
    | Define queues that will be created and bound to exchanges
    | Uncomment and configure as needed
    |
    */

    // 'queues' => [
    //     'orders.created' => [
    //         'durable' => true,
    //         'auto_delete' => false,
    //         'exchange' => 'orders',
    //         'routing_key' => '',
    //     ],
    //     'orders.updated' => [
    //         'durable' => true,
    //         'auto_delete' => false,
    //         'exchange' => 'orders',
    //         'routing_key' => '',
    //     ],
    //     'orders.cancelled' => [
    //         'durable' => true,
    //         'auto_delete' => false,
    //         'exchange' => 'orders',
    //         'routing_key' => '',
    //     ],
    //     'notifications.email' => [
    //         'durable' => true,
    //         'auto_delete' => false,
    //         'exchange' => 'notifications',
    //         'routing_key' => 'email',
    //     ],
    // ],

];