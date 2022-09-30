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
use Twipsi\Components\Notification\Interfaces\DatabaseNotificationInterface as DatabaseNotification;
use Twipsi\Components\Notification\Messages\DatabaseMessage;
use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;
use Twipsi\Facades\DB;

class DatabaseDriver implements NotificationDriverInterface
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
        $query = DB::open('tw_notifications');
        $query->insert($this->compileData($notifiable, $notification));

        // Create the notification in the database.
        // $notifiable->recipients('database')->create(
        //     $this->compileData($notifiable, $notification)
        // );
    }

    /**
     * Get the message from the database notification.
     * 
     * @param Notifiable $notifiable
     * @param array $notification
     * 
     * @return array
     */
    public function getMessage(Notifiable $notifiable, DatabaseNotification $notification): DatabaseMessage|array
    {
        if(! is_null($message = $notification->toDatabase())) {
            return $message;
        }

        if(! is_null($message = $notification->toArray($notifiable))) {
            return $message;
        }

        throw new RuntimeException("No valid data provided by the database notification");
    }

    /**
     * Compile the required data to be used.
     * 
     * @param Notifiable $notifiable
     * @param DatabaseNotification $notification
     * 
     * @return array
     */
    public function compileData(Notifiable $notifiable, DatabaseNotification $notification): array 
    {
        return [
            'id' => $notification->id,
            'uid' => $notifiable->id,
            'type' => get_class($notification),
            'data' => $this->getMessage($notifiable, $notification),
            'read_at' => null,
        ];
    }
}
