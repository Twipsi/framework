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

namespace Twipsi\Components\Notification\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Components\Notification\Notification;
use Twipsi\Components\User\Interfaces\INotifiable;
use Twipsi\Support\Traits\Serializable;

class NotificationSentEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The notifiable.
     */
    public INotifiable $notifiable;

    /**
     * The notification object,
     * 
     * @var Notification
     */
    public Notification $notification;

    /**
     * The Channel sent through.
     * 
     * @var string
     */
    public string $channel;

    /**
     * Create a new event data holder.
     */
    public function __construct($notifiable, $notification, string $channel)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
        $this->channel = $channel;
    }

    /**
     * Return the event payload.
     */
    public function payload(): array
    {
        return ["notifiable" => $this->notifiable, 'notification' => $this->notification, 'channel' => $this->channel];
    }
}
