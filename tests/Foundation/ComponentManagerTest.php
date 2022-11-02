<?php

namespace Twipsi\Tests\Foundation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentManager;
use Twipsi\Tests\Foundation\Fakes\ADriver;
use Twipsi\Tests\Foundation\Fakes\BDriver;
use Twipsi\Tests\Foundation\Fakes\ExampleComponent;

class ComponentManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * The test data.
     *
     * @var ComponentManager
     */
    protected ComponentManager $data;

    /**
     * Tear down.
     *
     * @return void
     */
    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->data = new ExampleComponent(new Application());
    }

    public function testDefaultDefaultDriver()
    {
        $this->assertSame(
            $this->data->getDefaultDriver(),
            'c_driver'
        );
    }

    public function testSetDefaultDriver()
    {
        $this->data->setDefaultDriver('a_driver');

        $this->assertSame(
            $this->data->getDefaultDriver(),
            'a_driver'
        );
    }

    public function testManagerShouldUseDefaultDriverIfNotProvided()
    {
        $this->data->setDefaultDriver('a_driver');
        $driver = $this->data->driver();

        $this->assertInstanceOf( ADriver::class,
            $driver
        );
    }

    public function testManagerShouldCreateProvidedDriver()
    {
        $driver = $this->data->driver('b_driver');

        $this->assertInstanceOf( BDriver::class,
            $driver
        );
    }

    public function testManagerShouldThrowExceptionOnNotSupportedDriver()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->data->driver('d_driver');
    }

    public function testManagerShouldSaveResolvedDriver()
    {
        $this->data->driver('b_driver');
        $this->data->driver('c_driver');

        $this->assertTrue(
            $this->data->has('b_driver')
        );
        $this->assertTrue(
            $this->data->has('c_driver')
        );
        $this->assertFalse(
            $this->data->has('a_driver')
        );
    }

    public function testManagerShouldForgetResolvedDriver()
    {
        $this->data->driver('b_driver');
        $this->data->driver('c_driver');

        $this->assertTrue(
            $this->data->has('b_driver')
        );

        $this->data->forget('b_driver');

        $this->assertFalse(
            $this->data->has('b_driver')
        );
    }

    public function testManagerShouldExtendDriver()
    {
        $this->data->extend('d_driver', function($value) {
            return $value;
        });

        $this->assertTrue(
            $this->data->has('d_driver')
        );

        $this->assertSame(
            $this->data->driver('d_driver', 'print'),
            'print'
        );
    }

    public function testManagerShouldForwardCallsToDriver()
    {
        $mock = \Mockery::mock('Twipsi\Tests\Foundation\Fakes\CDriver');
        $mock->shouldReceive('query')
            ->once()
            ->with('test');

        $this->data->override('c_driver', $mock);
        $this->data->setDefaultDriver('c_driver');
        $this->data->query('test');
    }

    public function testManagerShouldForwardCallsToExtendedDriver()
    {
        $mock = \Mockery::mock('Twipsi\Tests\Foundation\Fakes\CDriver');
        $mock->shouldReceive('query')
            ->once()
            ->with('test');

        $this->data->extend('d_driver', function() use($mock) {
            return $mock;
        });

        $this->data->setDefaultDriver('d_driver');
        $this->data->query('test');
    }
}