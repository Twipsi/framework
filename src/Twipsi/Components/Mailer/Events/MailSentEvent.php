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

use Symfony\Component\Mailer\SentMessage;
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Support\Traits\Serializable;

class MailSentEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The mailer message.
     */
    public SentMessage $message;

    /**
     * Create a new event data holder.
     */
    public function __construct(SentMessage $message)
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
