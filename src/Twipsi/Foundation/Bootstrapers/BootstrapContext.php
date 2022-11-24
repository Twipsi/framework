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

namespace Twipsi\Foundation\Bootstrapers;

use Closure;
use Twipsi\Foundation\Application\Application;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Foundation\Env;

class BootstrapContext
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Construct Bootstrapper.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     */
    public function invoke(): void
    {
        $this->app->setContext(
            $this->getContextLoader()
        );
    }

    /**
     * Get the context loading closure.
     *
     * @return Closure
     */
    protected function getContextLoader():  Closure
    {
        return $this->app->isTest()
            ? fn() => Env::get('TEST_CONTEXT', '')
            : fn($app) => $app->get(Route::class)->getContext();
    }
}
