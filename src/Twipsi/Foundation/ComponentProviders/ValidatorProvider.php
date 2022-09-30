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

use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Validator\DatabaseVerifier;
use Twipsi\Components\Validator\ValidatorFactory;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class ValidatorProvider extends ComponentProvider
{
  /**
   * Register service provider.
   */
  public function register(): void
  {
    // Bind the validator factory to the application.
    $this->app->keep('validator', function (Application $app) {

        $dbverifier = new DatabaseVerifier($app->get('db.connection'));

        return new ValidatorFactory($app->translator, $dbverifier);
    });

    // Append validator to request object. 
    $this->app->rebind('request', function (Application $app, HttpRequest $request) {

        $request->setValidator(
            fn () => $app->validator
        );
    });

  }
}
