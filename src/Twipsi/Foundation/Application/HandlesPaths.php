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

namespace Twipsi\Foundation\Application;

trait HandlesPaths
{
    /**
     * System path registry.
     *
     * @var PathRegistry
     */
    protected PathRegistry $paths;

    /**
     * Set current system scope paths.
     *
     * @param string $path
     *
     * @return void
     */
    public function setBasePaths(string $path = ''): void
    {
        $this->paths = new PathRegistry($path);
    }

     /**
     * Return a path from the path registry.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function path(string $name): ?string
    {
        return $this->paths[$name];
    }

    /**
     * Return a path registry.
     *
     * @return PathRegistry
     */
    public function nav(): PathRegistry
    {
        return $this->paths;
    }
}