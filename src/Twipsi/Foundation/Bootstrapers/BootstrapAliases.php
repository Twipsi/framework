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

class BootstrapAliases
{
    /**
     * Contruct Bootstrapper.
     */
    public function __construct(protected Application $app){}

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     */
    public function invoke(): void
    {
        $this->app->aliases()->merge(
            $this->app->config->get('component.aliases')
        );
    }
}
