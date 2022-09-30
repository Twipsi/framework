<?php

namespace Twipsi\Components\Database\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Support\Traits\Serializable;

class TransactionCommitedEvent implements EventInterface
{
    use Dispatchable, Serializable;

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