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

use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;

abstract class Notification
{
    /**
     * The ID of the notification.
     * 
     * @var string
     */
    public string $id;

    /**
     * The locale to use when sending the notification.
     * 
     * @var string
     */
    public ?string $locale = null;

    /**
     * Set the notification locale.
     * 
     * @param string $locale
     * 
     * @return Notification
     */
    public function locale(string $locale): Notification
    {
        $this->locale = $locale;

        return $this;
    }

    /**
    * The method that should be implemented to determine
    * which driver(s) we are using.

    * @param array|null $driver
    * 
    * @return array
    */
    abstract function via(Notifiable $notifiable): array;
}