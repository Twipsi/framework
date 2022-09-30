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

namespace Twipsi\Components\Notification\Interfaces;

use Twipsi\Components\Notification\Messages\BroadcastMessage;

interface BroadcastNotificationInterface
{
    /**
     * Method to retrieve a valid BroadcastMessage;
     * 
     * @return BroadcastMessage
     */
    public function toBroadcast(): BroadcastMessage;


    /**
     * Method to retrieve a valid BroadcastMessage as array;
     * 
     * @param mixed $notifiable
     * 
     * @return BroadcastMessage
     */
    public function toArray(mixed $notifiable): BroadcastMessage;
}
