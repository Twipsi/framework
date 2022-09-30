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

namespace Twipsi\Components\Router\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Support\Traits\Serializable;

final class RouteMatchedEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The route item.
     *
     * @var Route
     */
    public Route $route;

    /**
     * The uri matched.
     *
     * @var string
     */
    public string $url;

    /**
     * Create a new event data holder.
     *
     * @param Route $route
     * @param string $url
     */
    public function __construct(Route $route, string $url)
    {
        $this->route = $route;
        $this->url = $url;
    }

    /**
     * Return the event payload
     *
     * @return array
     */
    public function payload(): array
    {
        return ['route' => $this->route, 'url' => $this->url];
    }
}
