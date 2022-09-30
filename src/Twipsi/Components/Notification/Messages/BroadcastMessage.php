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

namespace Twipsi\Components\Notification\Messages;

class BroadcastMessage
{
    /**
     * The data for the notification.
     * 
     * @var array
     */
    public array $data;

    /**
     * Set the data for the notifiaction.
     * 
     * @param array $data
     * 
     * @return DatabaseMessage
     */
    public function setData(array $data): BroadcastMessage
    {
        $this->data = $data;

        return $this;
    }
}