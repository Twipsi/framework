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

namespace Twipsi\Foundation\ComponentProviders;

use Twipsi\Components\Security\Encrypter;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class EncrypterProvider extends ComponentProvider
{
  /**
  * Register service provider.
  */
  public function register(): void
  {
    $this->app->keep('encrypter', function (Application $app) {
      return new Encrypter($app->config->get('security.app_key'),
                           $app->config->get('security.encrypter'));
    });
  }
}
