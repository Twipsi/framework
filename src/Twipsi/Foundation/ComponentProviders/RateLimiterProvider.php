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

use Twipsi\Components\RateLimiter\LimiterCache;
use Twipsi\Components\RateLimiter\RateLimiter;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class RateLimiterProvider extends ComponentProvider
{
    /**
     * Register service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind the notification manager to the application.
        $this->app->keep('ratelimiter', function (Application $app) {
            $limiter = new LimiterCache($app->get('config')
                ->get('cache.limiter'));

            return new RateLimiter($limiter);
        });
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['ratelimiter'];
    }
}
