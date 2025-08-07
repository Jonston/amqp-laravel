<?php

namespace Jonston\AmqpLaravel;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPService
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $vhost;

    public function __construct()
    {
        $this->host = config('amqp.host', 'localhost');
        $this->port = config('amqp.port', 5672);
        $this->user = config('amqp.user', 'guest');
        $this->password = config('amqp.password', 'guest');
        $this->vhost = config('amqp.vhost', '/');
    }

    /**
     * @throws \Exception
     */
    public function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );
        }

        return $this->connection;
    }

    /**
     * @throws \Exception
     */
    public function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->channel = $this->getConnection()->channel();
        }

        return $this->channel;
    }

    /**
     * Публикация сообщения в обменник.
     *
     * @param string $exchange
     * @param string $routingKey
     * @param string $message
     * @param string $exchangeType
     * @param array $properties
     * @throws \Exception
     */
    public function publish(
        string $exchange,
        string $routingKey,
        string $message,
        string $exchangeType = 'direct',
        array $properties = []
    ): void {
        $channel = $this->getChannel();
        $channel->exchange_declare($exchange, $exchangeType, false, true, false);
        $channel->basic_publish(
            new AMQPMessage($message, $properties),
            $exchange,
            $routingKey
        );
    }

    /**
     * Подписка на очередь и обработка сообщений.
     *
     * @param string $queue
     * @param callable $callback
     * @param string $exchange
     * @param string $exchangeType
     * @param string $routingKey
     * @throws \Exception
     */
    public function consume(
        string $queue,
        callable $callback,
        string $exchange = '',
        string $exchangeType = 'direct',
        string $routingKey = ''
    ): void {
        $channel = $this->getChannel();
        if ($exchange) {
            $channel->exchange_declare($exchange, $exchangeType, false, true, false);
            $channel->queue_declare($queue, false, true, false, false);
            $channel->queue_bind($queue, $exchange, $routingKey);
        } else {
            $channel->queue_declare($queue, false, true, false, false);
        }
        $channel->basic_consume($queue, '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function close(): void
    {
        if ($this->channel && $this->channel->is_open()) {
            $this->channel->close();
        }

        if ($this->connection && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}