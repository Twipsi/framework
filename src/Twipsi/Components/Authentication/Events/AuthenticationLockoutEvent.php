<?php
declare(strict_types=1);

/*
 * This file is part of the Console CRM package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Components\Authentication\Events;

use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Support\Traits\Serializable;

class AuthenticationLockoutEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The stringified index identifier
     * @var string
     */
    public static $index = "authentication.lockout";

    /**
     * Request service.
     */
    public HttpRequest $request;

    /**
     * Create a new event data holder.
     */
    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return ["request" => $this->request];
    }
}
