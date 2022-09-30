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

use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class CookieProvider extends ComponentProvider
{
  /**
  * Register service provider.
  */
  public function register(): void
  {
    // Bind the session handler to the application.
    $this->app->keep('cookie', function (Application $app) {
      return $app->request->cookies();
    });
  }
}
