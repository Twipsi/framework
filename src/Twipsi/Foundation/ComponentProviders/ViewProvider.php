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

use Twipsi\Components\View\ViewCache;
use Twipsi\Components\View\ViewFactory;
use Twipsi\Components\View\ViewLocator;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class ViewProvider extends ComponentProvider
{
    /**
     * Register service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind the view factory to the application.
        $this->app->keep('view.factory', function (Application $app) {
            $factory = new ViewFactory($app->get('view.locator'), $app->get('view.cache'));

            // Queue the application to inject components.
            $factory->queue("__app", $app);

            return $factory;
        });

        // Bind the view locator to the application.
        $this->app->keep('view.locator', function (Application $app) {

            return new ViewLocator(
                $app->get('config')->get('view.path'),
                $app->get('config')->get('view.theme')
            );
        });

        // Bind the view cache to the application.
        $this->app->keep('view.cache', function (Application $app) {

            return new ViewCache(
                $app->get('config')->get('view.cache.path'),
                $app->get('config')->get('view.cache.usecache'),
                $app->get('config')->get('view.cache.extension')
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
        return ['view.factory', 'view.locator', 'view.cache'];
    }
}
