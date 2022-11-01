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

namespace Twipsi\Support\Applicables;

use Twipsi\Foundation\Application\Application;

trait App
{
    protected Application $app;

    /**
     * Set the config instance
     */
    public function appendApp(Application $app): static
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get a config element.
     */
    public function getApplication(): mixed
    {
        return $this->app;
    }
}
