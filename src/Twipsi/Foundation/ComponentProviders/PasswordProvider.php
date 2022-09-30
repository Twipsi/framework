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

use Twipsi\Components\Password\PasswordManager;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class PasswordProvider extends ComponentProvider
{
    /**
     * Register service provider.
     */
    public function register(): void
    {
        $this->app->keep("auth.password.manager", function (Application $app) {
            return new PasswordManager($app);
        });

        $this->app->keep("auth.password.driver", function (Application $app) {
            return $app->get("auth.password.manager")->driver();
        });
    }
}
