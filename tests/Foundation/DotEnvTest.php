<?php

namespace Twipsi\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Foundation\DotEnv;
use Twipsi\Foundation\Env;

class DotEnvTest extends TestCase
{
    /**
     * Setup test environment.
     *
     * @return void
     * @throws FileNotFoundException
     */
    protected function setUp(): void
    {
        (new DotEnv('./tests/Foundation/Fakes/.env'))->load();
    }

    public function testGetEnv()
    {
        $this->assertSame(
            Env::get('APP_KEY'),
            'thisisthekey'
        );
    }

    public function testDefaultEnv()
    {
        $this->assertSame(
            Env::get('APP_NON', 'doesnt exist'),
            'doesnt exist'
        );
    }

    public function testEnvShouldDismissComments()
    {
        $this->assertNull(
            Env::get('CACHE_ENV')
        );
    }

    public function testEnvShouldReplaceVariadicParameters()
    {
        $this->assertSame(
            Env::get('CACHE_PREFIX'),
            'Twipsi_cache_'
        );
    }
}