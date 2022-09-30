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

use Twipsi\Components\Http\Exceptions\NotSupportedException;
use Twipsi\Components\Http\RequestFactory;

final class AuthTest extends Sandbox
{
  public function testAuthenticationShouldBuildSupportedDrivers(): void
  {
    $auth = $this->app->get('auth.manager');

    $drivers = ['web', 'api'];

    foreach ($drivers as $driver){

      try {
        $driver = $auth->driver($driver);

      } catch (NotSupportedException $e) {
        $this->fail();
      }
    }

    $this->assertTrue(TRUE);
  }

//  public function testAuthenticationShouldLoginWithSession(): void
//  {
//    $auth = $this->app->get('auth.manager')->driver('web');
//
//    if(! $auth->check()) {
//      $auth->attempt(['email' => 'admin@twipsi.com', 'password' => 'Mantys01#']);
//    }
//
//    $user = $auth->user();
//
//    $this->assertInstanceOf('Twipsi\Components\User\Interfaces\IAuthenticatable', $user);
//  }
//
//  public function testAuthenticationShouldLoginWithSessionViaRemember(): void
//  {
//    $auth = $this->app->get('auth.manager')->driver('web');
//
//    if(! $auth->check()) {
//      $auth->attempt(['email' => 'admin@twipsi.com', 'password' => 'Mantys01#'], true);
//    }
//
//    $user = $auth->user();
//
//    $this->assertInstanceOf('Twipsi\Components\User\Interfaces\IAuthenticatable', $user);
//  }
//
//  public function testAuthenticationAPIShouldNotValidateWithoutKey(): void
//  {
//    $auth = $this->app->get('auth.manager')->driver('api');
//
//    if(! $auth->check()) {
//      $user = $auth->validate(['email' => 'admin@twipsi.com']);
//    }
//
//    $this->assertFalse($user);
//  }
//
//  public function testAuthenticationAPIShouldValidateWithKey(): void
//  {
//    $auth = $this->app->get('auth.manager')->driver('api');
//    $name = $this->app->get('config')->get('auth.drivers.api.api_storage_column');
//
//    if(! $auth->check()) {
//      $user = $auth->validate([$name => '164ae2659423a7267d3248bdd96ffd15']);
//    }
//
//    $this->assertTrue($user);
//  }
//
//  public function testAuthenticationAPIShouldValidateUserFromRequestInput(): void
//  {
//    $name = $this->app->get('config')->get('auth.drivers.api.api_input_field');
//
//    $request = RequestFactory::create([],['api_token' => '164ae2659423a7267d3248bdd96ffd15']);
//    $request->setMethod('GET');
//    $this->app->instance('request', $request);
//
//    $auth = $this->app->get('auth.manager')->driver('api');
//    $user = $auth->user();
//
//    $this->assertInstanceOf('Twipsi\Components\User\Interfaces\IAuthenticatable', $user);
//  }
//
//  public function testAuthenticationAPIShouldValidateUserFromRequestPostInput(): void
//  {
//    $name = $this->app->get('config')->get('auth.drivers.api.api_input_field');
//
//    $request = RequestFactory::create([],[],['api_token' => '164ae2659423a7267d3248bdd96ffd15']);
//    $request->setMethod('POST');
//
//    $this->app->instance('request', $request);
//
//    $auth = $this->app->get('auth.manager')->driver('api');
//    $user = $auth->user();
//
//    $this->assertInstanceOf('Twipsi\Components\User\Interfaces\IAuthenticatable', $user);
//  }
}
