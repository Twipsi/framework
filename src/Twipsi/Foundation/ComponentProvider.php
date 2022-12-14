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
use Twipsi\Foundation\ComponentProviders\DeferredComponentProvider;

abstract class ComponentProvider
{
    /**
     * Application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Service provider constructor.
     *
     * @param Application $app
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
