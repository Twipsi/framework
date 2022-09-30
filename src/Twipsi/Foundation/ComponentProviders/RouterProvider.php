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

use Twipsi\Components\Router\RouteBag;
use Twipsi\Components\Router\RouteFactory;
use Twipsi\Components\Router\Router;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class RouterProvider extends ComponentProvider
{
    /**
     * Register service provider.
     */
    public function register(): void
    {
        // Bind the route subscriber to the application.
        $this->app->keep('route.factory', function (Application $app) {
            return (new RouteFactory(new RouteBag($app)))->setApp($app);
        });

        // Bind the router to the application.
        $this->app->keep('route.router', function (Application $app) {
            return new Router(
                $app->get('request'),
                $app->get('route.factory'),
                $app->get('events'),
                $app
            );
        });

        // Bind the route collection bag to the application.
        $this->app->keep('route.routes', function (Application $app) {
            return $app->get('route.router')->getRoutes();
        });
    }

}
