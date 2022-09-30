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

namespace Twipsi\Components\Password\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Support\Traits\Serializable;

class PasswordResetEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * Resolved middlewares.
     */
    public Authable $user;

    /**
     * Create a new event data holder.
     */
    public function __construct(Authable $user)
    {
        $this->user = $user;
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return ["user" => $this->user];
    }
}
