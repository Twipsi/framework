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

use Twipsi\Components\Authentication\AuthenticationManager;
use Twipsi\Components\Authorization\AccessManager;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class AuthenticationProvider extends ComponentProvider
{
    /**
     * Register service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->bindAuthentication();
        $this->bindUserToContainers();
        $this->bindAuthorization();
    }

    /**
     * Bind all the authentication components to the application.
     *
     * @return void
     */
    protected function bindAuthentication(): void
    {
        $this->app->keep("auth.manager", function (Application $app) {
            return new AuthenticationManager($app);
        });

        $this->app->keep("auth.driver", function (Application $app) {
            return $app->get("auth.manager")->driver();
        });
    }

    /**
     * Bind the authenticated user to Request and Application.
     *
     * @return void
     */
    protected function bindUserToContainers(): void
    {
        // Attach user loader to request object.
        $this->app->rebind('request', function (Application $app, HttpRequest $request) {

            $request->attachUser(function ($driver = null) use ($app) {
                return call_user_func(
                    $app["auth.manager"]->getUserLoader(), $driver
                );
            });
        });

        // Attach user loader to app.
        $this->app->bind("user", function (Application $app) {
            return call_user_func($app["auth.manager"]->getUserLoader());
        });
    }

    /**
     * Bind all the authorization components to the application.
     *
     * @return void
     */
    protected function bindAuthorization(): void
    {
        $this->app->keep("auth.access", function (Application $app) {
            return new AccessManager(
                $app["auth.manager"]->getUserLoader()
            );
        });
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['auth.manager', 'auth.driver', 'auth.access', 'user'];
    }
}
