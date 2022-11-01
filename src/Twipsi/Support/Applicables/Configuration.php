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

use Twipsi\Foundation\ConfigRegistry;

trait Configuration
{
    protected ConfigRegistry $config;

    /**
     * Set the config instance
     */
    public function appendConfiguration(ConfigRegistry $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get a config element.
     */
    public function config(string $config): mixed
    {
        return $this->config->get($config);
    }

}
