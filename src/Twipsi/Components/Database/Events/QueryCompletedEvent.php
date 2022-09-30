<?php

namespace Twipsi\Components\Database\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Support\Traits\Serializable;

class QueryCompletedEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The query completed
     *
     * @var string
     */
    public string $query;

    /**
     * Create a new event data holder.
     *
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->query = $query;
    }

    /**
     * Return the event payload
     *
     * @return array
     */
    public function payload(): array
    {
        return ['query' => $this->query];
    }
}