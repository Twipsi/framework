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

namespace Twipsi\Foundation;

use Twipsi\Foundation\Application\Application;

abstract class ComponentProvider
{
    /**
     * Application instance.
     */
    protected Application $app;

    /**
     * Service provider constructor.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Check if the loader should be deferred.
     *
     * @return bool
     */
    public function deferred(): bool
    {
        return $this instanceof DeferredComponentProvider;
    }

    /**
     * Must implement method.
     *
     * @return void
     */
    abstract function register(): void;
}
