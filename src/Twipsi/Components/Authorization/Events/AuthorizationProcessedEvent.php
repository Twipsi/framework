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

namespace Twipsi\Components\Authorization\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Support\Traits\Serializable;

class AuthorizationProcessedEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The user authorized.
     */
    public ?Authable $user;

    /**
     * The action authorized.
     */
    public string $action;

    /**
     * The authorization result.
     */
    public string $result;

    /**
     * Create a new event data holder.
     */
    public function __construct(?Authable $user, string $action, bool $result)
    {
        $this->user = $user;
        $this->action = $action;
        $this->result = $result ? "success" : "failed";
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return [
            "user" => $this->user,
            "action" => $this->action,
            "result" => $this->result,
        ];
    }
}
