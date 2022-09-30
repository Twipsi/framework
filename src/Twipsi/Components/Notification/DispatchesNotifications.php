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

namespace Twipsi\Components\Notification;

use Twipsi\Components\Notification\Notification;

trait DispatchesNotifications
{
    /**
     * Send the notification normaly.
     * 
     * @param Notification $notification
     * 
     * @return void
     */
    public function notify(Notification $notification): void
    {
        $this->app->get('notification')
            ->send([$this], $notification);
    }

    /**
     * Send the notification asap.
     * 
     * @param Notification $notification
     * @param array|null $channels
     * 
     * @return void
     */
    public function notifyNow(Notification $notification, array $channels = null): void 
    {
        $this->app->get('notification')
            ->sendNow([$this], $notification, $channels);
    }

    /**
     * Get the To address based on the driver.
     * 
     * @param string $driver
     * 
     * @return mixed
     */
    public function recipients(string $driver): mixed
    {
        //If we have a custom method to provide recipients.
        if(method_exists($this, $method = 'recipientsFor.'.ucfirst($driver))) {
            return $this->{$method}();
        }

        return match($driver) {
            'database' => $this->notifications(), // ?
            'mail'     => [$this->email, $this->name ?? null],
            default    => null, 
        };
    }
}
