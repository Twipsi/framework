<?php
declare (strict_types = 1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Foundation\ComponentProviders;

use Twipsi\Components\Http\Response\ResponseFactory;
use Twipsi\Components\Url\Redirector;
use Twipsi\Components\Url\UrlGenerator;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;
use Twipsi\Foundation\ConfigRegistry;

class ResponseProvider extends ComponentProvider
{
    /**
     * Register component provider.
     * 
     * @return void
     */
    public function register(): void
    {
        // Bind the url generator to the application.
        $this->app->keep('url', function (Application $app) {
            return new UrlGenerator($app->get('request'), $app->get('route.routes'));
        });

        // Rebind and set the session loader.
        $this->app->extend('url', function (Application $app, UrlGenerator $url) {
            $url->setSessionLoader(
              fn() => $app->get('session.store')
            );
        });

        $this->app->extend('url', function (Application $app, UrlGenerator $url) {
            $url->setSystemKey(
                fn() => $app->get('config')->get('security.app_key')
            );
        });

        // Bind the redierctor to the application.
        $this->app->keep('redirector', function (Application $app) {
            return new Redirector($app->get('request'), $app->get('url'));
        });

        // Bind the response factory to the application.
        $this->app->keep('response', function (Application $app) {
            return new ResponseFactory($app->get('redirector'));
        });
    }

}
