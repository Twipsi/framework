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

namespace Twipsi\Facades;

use Twipsi\Facades\Interfaces\FacadeInterface;

/**
 * @method static get(string $string, \Closure $param)
 * @method static any(string $string, \Closure $param)
 */
class Route extends Facade implements FacadeInterface
{
    /**
     * Get the accessor name.
     */
    public static function getFacadeAccessorName(): string
    {
        return 'route.factory';
    }

    /**
     * Get the class with namespace to load.
     */
    public static function getFacadeClassName(): string
    {
        return 'Twipsi\Components\Router\RouteFactory';
    }

}
