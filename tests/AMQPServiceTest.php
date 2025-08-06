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
}