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

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Components\Mailer\MailMessage;
use Twipsi\Support\Traits\Serializable;

class MailSendingEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The mailer message.
     */
    public MailMessage $message;

    /**
     * Create a new event data holder.
     */
    public function __construct(MailMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return ["message" => $this->message];
    }
}
