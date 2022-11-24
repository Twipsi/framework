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
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class CookieProvider extends ComponentProvider
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
        // Bind the cookie handler to the application.
        $this->app->keep('cookie', function (Application $app) {
            return $app->get('request')->cookies();
        });
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['cookie'];
    }
}
