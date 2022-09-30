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

namespace Twipsi\Components\Notification\Drivers\Interfaces;

use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;

interface NotificationDriverInterface
{
    /**
     * Initiate database sending.
     * 
     * @param Notifiable $notifiable
     * @param mixed $notification
     * 
     * @return void
     */
    public function send(Notifiable $notifiable, mixed $notification): void;
}
