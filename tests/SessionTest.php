<?php
declare(strict_types=1);

/*
* This file is part of the Twipsi package.
*
* (c) Petrik Gábor <twipsi@twipsi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Twipsi\Tests;

use PHPUnit\Framework\TestCase;
use Twipsi\Facades\Session;
use Twipsi\Components\Session\SessionItem;
use Twipsi\Components\Http\Exceptions\NotSupportedException;

final class SessionTest extends TestCase
{
  use CreatesApp;

  public function testSessionShouldBuildSupportedDrivers(): void
  {
    $app = $this->createApplication();

    $handler = $app->call('session.handler');
    $drivers = ['array', 'global', 'file', 'cookie'];

    foreach ($drivers as $driver){

      try {
        $session = $handler->driver($driver);
      } catch (\Throwable $e) {
        $this->fail();
      }
    }

    $this->assertTrue(TRUE);
  }

  public function testSessionShouldThrowUnsupported(): void
  {
    $app = $this->createApplication();

    $this->expectException(NotSupportedException::class);

    $handler = $app->call('session.handler');
    $handler->driver('redis');
  }

  public function testSessionShouldThrowRuntimeException(): void
  {
    $app = $this->createApplication();

    $this->expectException(\RuntimeException::class);

    $app->config->push('session', ['driver' => null]);

    $handler = $app->call('session.handler');
    $handler->driver();
  }

  public function testSessionShouldReturnParameterValues(): void
  {
    $app = $this->createApplication();

    $handler = $app->call('session.handler');
    $session = $handler->driver('file');
    $session->set('_user.token', '@tokenhash');

    $this->assertEquals('@tokenhash', $session->get('_user.token'), "The expected session attribute is set.");
  }

}
