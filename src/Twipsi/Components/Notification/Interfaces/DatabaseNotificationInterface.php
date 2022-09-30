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

use Twipsi\Components\Notification\Messages\DatabaseMessage;

interface DatabaseNotificationInterface
{
    /**
     * Method to retrieve a valid DatabaseMessage;
     * 
     * @return DatabaseMessage
     */
    public function toDatabase(): DatabaseMessage;

    /**
     * Method to retrieve a valid DatabaseMessage as array;
     * 
     * @param mixed $notifiable
     * 
     * @return array
     */
    public function toArray(mixed $notifiable): array;
}
