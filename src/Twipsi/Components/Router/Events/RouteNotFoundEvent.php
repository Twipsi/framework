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
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Support\Traits\Serializable;

final class RouteNotFoundEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The request instance.
     *
     * @var HttpRequest
     */
    public HttpRequest $request;

    /**
     * Create a new event data holder.
     *
     * @param HttpRequest $request
     */
    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Return the event payload
     *
     * @return array
     */
    public function payload(): array
    {
        return ['request' => $this->request];
    }
}
