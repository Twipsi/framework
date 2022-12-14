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

use ReflectionException;
use Twipsi\Components\Session\SessionHandler;
use Twipsi\Components\Session\SessionSubscriber;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class SessionProvider extends ComponentProvider
{
    /**
     * Register service provider.
     *
     * @return void
     * @throws ReflectionException
     * @throws ApplicationManagerException
     */
    public function register(): void
    {
        // Bind the session handler to the application.
        $this->app->keep('session.subscriber', function (Application $app) {
            return new SessionSubscriber($app->get('config'), $app->get('encrypter'));
        });

        // Bind the session handler to the application.
        $this->app->keep('session.handler', function (Application $app) {
            return new SessionHandler($app->get('session.subscriber'));
        });

        // Bind the session handler to the application.
        $this->app->keep('session.store', function (Application $app) {
            return $app->make('session.handler')->driver();
        });
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['session.subscriber', 'session.handler', 'session.store'];
    }
}
