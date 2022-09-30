<?php

namespace Twipsi\Components\Database\Events;

use PDOStatement;
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Support\Traits\Serializable;

class StatementPreparedEvent implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The statement prepared
     *
     * @var PDOStatement
     */
    public PDOStatement $statement;

    /**
     * Create a new event data holder.
     *
     * @param PDOStatement $statement
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Return the event payload
     *
     * @return array
     */
    public function payload(): array
    {
        return ['statement' => $this->statement];
    }
}