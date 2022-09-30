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

namespace Twipsi\Foundation\Middleware;

use InvalidArgumentException;
use Twipsi\Foundation\Application\Application;
use Twipsi\Support\Str;

class MiddlewareLoader
{
    /**
     * Middleware constructor.
     *
     * @param protected Application $app
     */
    public function __construct(protected Application $app){}

    /**
     * Load the middlehandler.
     *
     * @param string $path
     *
     * @return [type]
     */
    public function load(string $path)
    {
        return new MiddlewareHandler($this->app, $this->discover($path));
    }
    
    /**
     * Discover middleware files.
     *
     * @param string $where
     *
     * @return array
     */
    public function discover(string $where): array
    {
        if (!is_dir($where)) {
            throw new InvalidArgumentException(sprintf("Directory [%s] could not be found", $where));
        }

        foreach (glob($where . "/*.php") as $filename) {
            if (Str::hay($filename)->resembles('general')) {
                $general = include $filename;
                continue;
            }

            if (Str::hay($filename)->resembles('group')) {
                $group = include $filename;
                continue;
            }

            if (Str::hay($filename)->resembles('simple')) {
                $simple = include $filename;
            }
        }

        return [($general ?? []), ($group ?? []), ($simple ?? [])];
    }
}
