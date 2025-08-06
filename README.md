# AMQP Laravel

Simple wrapper for AMQP library to use in Laravel applications.

## Installation

Install the package via Composer:

```bash
composer require jonston/amqp-laravel
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=amqp-config
```

This will create a `config/amqp.php` file where you can set your RabbitMQ connection parameters.

Add these variables to your `.env` file:

```env
AMQP_HOST=localhost
AMQP_PORT=5672
AMQP_USER=guest
AMQP_PASSWORD=guest
AMQP_VHOST=/
```

## Usage

Inject the AMQPService into your classes:

```php
<?php

namespace App\Http\Controllers;

use Jonston\AmqpLaravel\AMQPService;

class OrderController extends Controller
{
    public function __construct(
        private AMQPService $amqpService
    ) {}

    public function processOrder()
    {
        // Use the AMQP service
        $this->amqpService->publish('order.created', $orderData);
    }
}
```

Or resolve it from the container:

```php
$amqpService = app(AMQPService::class);
```

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

## License

MIT License