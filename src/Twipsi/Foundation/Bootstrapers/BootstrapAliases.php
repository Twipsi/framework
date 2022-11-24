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

use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class BootstrapAliases
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
     * @throws \ReflectionException
     * @throws ApplicationManagerException
     */
    public function invoke(): void
    {
        $this->app->aliases()->inject(
            $this->app->get('config')->get('component.aliases') ?? []
        );
    }
}
