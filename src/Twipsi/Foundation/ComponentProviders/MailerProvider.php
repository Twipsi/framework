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

use Twipsi\Components\Mailer\MailManager;
use Twipsi\Components\Mailer\Markdown;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class MailerProvider extends ComponentProvider implements DeferredComponentProvider
{
  /**
   * Register service provider.
   */
  public function register(): void
  {
    // Bind the mail manager to the application.
    $this->app->keep('mail.manager', function (Application $app) {
      return new MailManager($app);
    });

    // Bind the default mailer to the application.
    $this->app->keep('mail.mailer', function (Application $app) {
        return $app->get('mail.manager')->driver();
    });

    // Bind the default mailer to the application.
    $this->app->keep('mail.markdown', function (Application $app) {
        return new Markdown($app->get('view.factory'));
    });
  }

  /**
   * Return the components affected.
   * 
   * @return array
   */
  public function components(): array 
  {
    return ['mail.manager', 'mail.mailer', 'mail.markdown'];
  }
}
