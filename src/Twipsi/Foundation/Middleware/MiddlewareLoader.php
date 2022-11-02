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
     * The application object.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Middleware constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Load the middleware handler.
     *
     * @param string $path
     * @return MiddlewareRepository
     */
    public function load(string $path): MiddlewareRepository
    {
        return new MiddlewareRepository(...$this->discover($path));
    }

    /**
     * Discover middleware files.
     *
     * @param string $where
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
            }
            elseif (Str::hay($filename)->resembles('group')) {
                $group = include $filename;
            }
            elseif (Str::hay($filename)->resembles('simple')) {
                $simple = include $filename;
            }
        }

        return [($general ?? []), ($group ?? []), ($simple ?? [])];
    }
}
