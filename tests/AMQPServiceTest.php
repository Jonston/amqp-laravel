<?php

namespace Jonston\AmqpLaravel\Tests;

use Orchestra\Testbench\TestCase;
use Mockery;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Jonston\AmqpLaravel\AMQPService;

class AMQPServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('amqp.host', 'localhost');
        $app['config']->set('amqp.port', 5672);
        $app['config']->set('amqp.user', 'guest');
        $app['config']->set('amqp.password', 'guest');
        $app['config']->set('amqp.vhost', '/');
    }

    /**
     * @throws \Exception
     */
    public function test_connection_successful()
    {
        $mockConnection = Mockery::mock(AMQPStreamConnection::class);
        $mockConnection->shouldReceive('isConnected')->andReturn(true);

        $service = Mockery::mock(AMQPService::class)->makePartial();

        $service->shouldReceive('createConnection')->andReturn($mockConnection);
        $service->shouldReceive('getConnection')->andReturn($mockConnection);

        $connection = $service->getConnection();
        $this->assertTrue($connection->isConnected());
    }

    public function test_connection_with_custom_config()
    {
        config(['amqp.host' => 'test-host']);
        config(['amqp.port' => 1234]);
        config(['amqp.user' => 'test-user']);

        $service = new AMQPService();

        $reflection = new \ReflectionClass($service);

        $hostProperty = $reflection->getProperty('host');
        $portProperty = $reflection->getProperty('port');
        $userProperty = $reflection->getProperty('user');

        $this->assertEquals('test-host', $hostProperty->getValue($service));
        $this->assertEquals(1234, $portProperty->getValue($service));
        $this->assertEquals('test-user', $userProperty->getValue($service));
    }

    public function test_publish_message()
    {
        $mockChannel = Mockery::mock('PhpAmqpLib\Channel\AMQPChannel');
        $mockChannel->shouldReceive('exchange_declare')->once()->with('test-exchange', 'direct', false, true, false);
        $mockChannel->shouldReceive('basic_publish')->once();

        $service = Mockery::mock(AMQPService::class)->makePartial();
        $service->shouldReceive('getChannel')->andReturn($mockChannel);

        $service->publish('test-exchange', 'test-key', 'test-message', 'direct');

        $mockChannel->shouldHaveReceived('exchange_declare')->with('test-exchange', 'direct', false, true, false)->once();
        $mockChannel->shouldHaveReceived('basic_publish')->once();
        $this->assertTrue(true);
    }

    public function test_consume_message_with_exchange()
    {
        $mockChannel = Mockery::mock('PhpAmqpLib\Channel\AMQPChannel');
        $mockChannel->shouldReceive('exchange_declare')->once()->with('test-exchange', 'fanout', false, true, false);
        $mockChannel->shouldReceive('queue_declare')->once()->with('test-queue', false, true, false, false);
        $mockChannel->shouldReceive('queue_bind')->once()->with('test-queue', 'test-exchange', 'test-key');
        $mockChannel->shouldReceive('basic_consume')->once();
        $mockChannel->shouldReceive('is_consuming')->andReturn(false);

        $service = Mockery::mock(AMQPService::class)->makePartial();
        $service->shouldReceive('getChannel')->andReturn($mockChannel);

        $service->consume('test-queue', function () {}, 'test-exchange', 'fanout', 'test-key');

        $mockChannel->shouldHaveReceived('exchange_declare')->with('test-exchange', 'fanout', false, true, false)->once();
        $mockChannel->shouldHaveReceived('queue_declare')->with('test-queue', false, true, false, false)->once();
        $mockChannel->shouldHaveReceived('queue_bind')->with('test-queue', 'test-exchange', 'test-key')->once();
        $mockChannel->shouldHaveReceived('basic_consume')->once();
        $this->assertTrue(true);
    }

    public function test_consume_message_without_exchange()
    {
        $mockChannel = Mockery::mock('PhpAmqpLib\Channel\AMQPChannel');
        $mockChannel->shouldReceive('queue_declare')->once()->with('test-queue', false, true, false, false);
        $mockChannel->shouldReceive('basic_consume')->once();
        $mockChannel->shouldReceive('is_consuming')->andReturn(false);

        $service = Mockery::mock(AMQPService::class)->makePartial();
        $service->shouldReceive('getChannel')->andReturn($mockChannel);

        $service->consume('test-queue', function () {});

        $mockChannel->shouldHaveReceived('queue_declare')->with('test-queue', false, true, false, false)->once();
        $mockChannel->shouldHaveReceived('basic_consume')->once();
        $this->assertTrue(true);
    }

    public function test_declare_exchange()
    {
        $mockChannel = Mockery::mock('PhpAmqpLib\Channel\AMQPChannel');
        $mockChannel->shouldReceive('exchange_declare')
            ->once()
            ->with('my-exchange', 'topic', false, true, false, false, false, []);

        $service = Mockery::mock(AMQPService::class)->makePartial();
        $service->shouldReceive('getChannel')->andReturn($mockChannel);

        $service->declareExchange('my-exchange', 'topic');
        $mockChannel->shouldHaveReceived('exchange_declare')->with('my-exchange', 'topic', false, true, false, false, false, [])->once();
        $this->assertTrue(true);
    }

    public function test_declare_queue()
    {
        $mockChannel = Mockery::mock('PhpAmqpLib\Channel\AMQPChannel');
        $mockChannel->shouldReceive('queue_declare')
            ->once()
            ->with('my-queue', false, true, false, false, false, []);

        $service = Mockery::mock(AMQPService::class)->makePartial();
        $service->shouldReceive('getChannel')->andReturn($mockChannel);

        $service->declareQueue('my-queue');
        $mockChannel->shouldHaveReceived('queue_declare')->with('my-queue', false, true, false, false, false, [])->once();
        $this->assertTrue(true);
    }

    public function test_bind_queue()
    {
        $mockChannel = Mockery::mock('PhpAmqpLib\Channel\AMQPChannel');
        $mockChannel->shouldReceive('queue_bind')
            ->once()
            ->with('my-queue', 'my-exchange', 'my-key');

        $service = Mockery::mock(AMQPService::class)->makePartial();
        $service->shouldReceive('getChannel')->andReturn($mockChannel);

        $service->bindQueue('my-queue', 'my-exchange', 'my-key');
        $mockChannel->shouldHaveReceived('queue_bind')->with('my-queue', 'my-exchange', 'my-key')->once();
        $this->assertTrue(true);
    }

    public function test_setup_from_config()
    {
        config([
            'amqp.exchanges' => [
                'ex1' => ['type' => 'fanout', 'durable' => true],
                'ex2' => ['type' => 'direct', 'durable' => false],
            ],
            'amqp.queues' => [
                'q1' => ['durable' => true, 'exchange' => 'ex1', 'routing_key' => 'rk1'],
                'q2' => ['durable' => false],
            ],
        ]);

        $service = Mockery::mock(AMQPService::class)->makePartial();

        $service->shouldReceive('declareExchange')
            ->once()->with('ex1', 'fanout', false, true, false, false, false, []);
        $service->shouldReceive('declareExchange')
            ->once()->with('ex2', 'direct', false, false, false, false, false, []);
        $service->shouldReceive('declareQueue')
            ->once()->with('q1', false, true, false, false, false, []);
        $service->shouldReceive('declareQueue')
            ->once()->with('q2', false, false, false, false, false, []);
        $service->shouldReceive('bindQueue')
            ->once()->with('q1', 'ex1', 'rk1');

        $service->setupFromConfig();

        $service->shouldHaveReceived('declareExchange')->with('ex1', 'fanout', false, true, false, false, false, [])->once();
        $service->shouldHaveReceived('declareExchange')->with('ex2', 'direct', false, false, false, false, false, [])->once();
        $service->shouldHaveReceived('declareQueue')->with('q1', false, true, false, false, false, [])->once();
        $service->shouldHaveReceived('declareQueue')->with('q2', false, false, false, false, false, [])->once();
        $service->shouldHaveReceived('bindQueue')->with('q1', 'ex1', 'rk1')->once();
        $this->assertTrue(true);
    }
}