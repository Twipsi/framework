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

use Twipsi\Components\Events\EventHandler;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class EventProvider extends ComponentProvider
{
    /**
     * Boot component provider.
     * 
     * @return void
     */
    public function boot(): void 
    {
        $this->app->nav()
            ->set('path.events', 
                    $this->app->path('path.base').'\App\Events\Listeners'
            );
    }

    /**
     * Register component provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->keep('events', function (Application $app) {

            return new EventHandler($app);
        });
    }
}
