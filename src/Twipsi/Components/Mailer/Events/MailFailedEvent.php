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

namespace Twipsi\Components\Mailer\Events;

use Throwable;
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Support\Traits\Serializable;

class MailFailedEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The mailer message.
     */
    public Throwable $error;

    /**
     * Create a new event data holder.
     */
    public function __construct(Throwable $error)
    {
        $this->error = $error;
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return ["error" => $this->error];
    }
}
