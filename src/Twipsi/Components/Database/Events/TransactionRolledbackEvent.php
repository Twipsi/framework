<?php

namespace Twipsi\Components\Database\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;

class TransactionRolledbackEvent implements EventInterface
{
    /**
     * Create a new event data holder.
     */
    public function __construct(){}

    /**
     * Return the event payload
     *
     * @return array
     */
    public function payload(): array
    {
        return [];
    }
}