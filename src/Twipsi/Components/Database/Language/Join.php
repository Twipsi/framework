<?php

namespace Twipsi\Components\Database\Language;

final class Join
{
    /**
     * The join type.
     *
     * @var string
     */
    public string $type;

    /**
     * The table to join.
     *
     * @var string|Expression
     */
    public string|Expression $table;

    /**
     * Columns to join on.
     *
     * @var array
     */
    public array $columns = [];

    /**
     * @param string $type
     * @param string|Expression $table
     */
    public function __construct(string $type, string|Expression $table)
    {
        $this->type = $type;
        $this->table = $table;
    }

    /**
     * Create the ON part of the join.
     *
     * @param string $column
     * @param string $on
     * @param string $condition
     * @return $this
     */
    public function on(string $column, string $on, string $condition = 'and'): Join
    {
        $condition = count($this->columns) == 0 ? '' : ' '.$condition.' ';

        $this->columns[] = "{$condition}{$column} = $on";

        return $this;
    }

    /**
     * Append and OR condition to the clause.
     *
     * @param string $column
     * @param string $on
     * @return $this
     */
    public function or(string $column, string $on): Join
    {
        return $this->on($column, $on, 'or');
    }
}