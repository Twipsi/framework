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

class DatabaseMessage
{
    /**
     * The data to be saved with the message.
     * 
     * @var array
     */
    public array $data;

    /**
     * Set the datas to be stored with the notifiaction.
     * 
     * @param array $data
     * 
     * @return DatabaseMessage
     */
    public function SetData(array $data): DatabaseMessage
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the message data.
     * 
     * @return array
     */
    public function data(): array 
    {
        return $this->data;
    }
}