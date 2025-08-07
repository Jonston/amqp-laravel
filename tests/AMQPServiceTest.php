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
}