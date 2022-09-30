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

namespace Twipsi\Components\Notification\Drivers;

use RuntimeException;
use Twipsi\Components\Notification\Drivers\Interfaces\NotificationDriverInterface;
use Twipsi\Components\Notification\Interfaces\BroadcastNotificationInterface as BroadcastNotification;
use Twipsi\Components\Notification\Messages\BroadcastMessage;
use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;

class BroadcastDriver implements NotificationDriverInterface
{
    /**
     * Initiate database sending.
     * 
     * @param Notifiable $notifiable
     * @param mixed $notification
     * 
     * @return void
     */
    public function send(Notifiable $notifiable, mixed $notification): void
    {
        $message = $this->getMessage($notifiable, $notification);

        // Dispatch a queued event;
    }

    /**
     * Get the message from the database notification.
     * 
     * @param Notifiable $notifiable
     * @param BroadcastNotification $notification
     * 
     * @return array|BroadcastMessage
     */
    public function getMessage(Notifiable $notifiable, BroadcastNotification $notification): array|BroadcastMessage
    {
        if(! is_null($message = $notification->toBroadcast($notifiable))) {
            return $message;
        }

        if(! is_null($message = $notification->toArray($notifiable))) {
            return $message;
        }

        throw new RuntimeException("No valid data provided by the broadcast notification");
    }
}
