<?php

namespace Twipsi\Tests\Config;

use PHPUnit\Framework\TestCase;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\ConfigRegistry;

class ConfigTest extends TestCase
{
    /**
     * The test data.
     *
     * @var ConfigRegistry
     */
    protected ConfigRegistry $data;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->data = new ConfigRegistry(
            (new FileBag('tests/Config/fake', 'php'))->includeAll()
        );
    }

    public function testGetConfig()
    {
        $this->assertSame(
            $this->data->get('security.app_key'),
            'XXXKEy'
        );
        $this->assertSame(
            $this->data->get('security.keys.csrf_length'),
            64
        );
    }

    public function testShouldReturnRegistryIfMultipleEntriesFound()
    {
        $this->assertInstanceOf( ConfigRegistry::class,
            $this->data->get('security.keys')
        );
        $this->assertSame(
            $this->data->get('security.keys')->all(),
            [
                'secret_length' => 32,
                'csrf_length' => 64,
            ]
        );
        $this->assertSame(
            $this->data->get('security.keys')->get('secret_length'),
            32
        );
    }

    public function testSetConfig()
    {
        $config = clone($this->data)
            ->set('security.keys.new', 'thisisnew');

        $this->assertSame(
            $config->get('security.keys.new'),
            'thisisnew'
        );
    }

    public function testPushConfig()
    {
        $config = clone($this->data)
            ->push('security.keys.new', ['xxx', 'yyy']);

        $this->assertSame(
            $config->get('security.keys.new.0')->get(0),
            'xxx'
        );
    }
}