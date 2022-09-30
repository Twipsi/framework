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
use Twipsi\Support\Traits\Serializable;

class AuthenticationAttemptedEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * Resolved middlewares.
     */
    public array $credentials;

    /**
     * Create a new event data holder.
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return ["credentials" => $this->credentials];
    }
}
