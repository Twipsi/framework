<?php

namespace Twipsi\Components\Database\Builder;

use Closure;
use Exception;
use Twipsi\Components\Database\Language\Expression;
use Twipsi\Components\Database\Language\Join;
use Twipsi\Foundation\Exceptions\NotSupportedException;

trait BuildsJoins
{
    /**
     * Create a join clause.
     *
     * @param string $type
     * @param string|Expression $table
     * @param string|Closure|null $column
     * @param string|null $on
     * @return QueryBuilder|BuildsJoins
     */
    public function join(string $type, string|Expression $table, string|Closure $column = null, string $on = null): self
    {
        $join = new Join($type, $table);

        if(is_string($column)) {
            $join->on($this->expression->quote($column), $this->expression->quote($on));

        } elseif($column instanceof Closure) {
            $column($join);
        }

        $this->joins[] = $join;

        return $this;
    }

    /**
     * Create join from a sub select.
     *
     * @param string $type
     * @param string|QueryBuilder $query
     * @param string $alias
     * @param string|Closure|null $column
     * @param string|null $on
     * @return QueryBuilder|BuildsJoins
     * @throws NotSupportedException
     * @throws Exception
     */
    public function makeJoin(string $type, string|QueryBuilder $query, string $alias, string|Closure $column = null, string $on = null): self
    {
        [$query, $bindings] = $this->parseExpression($query);

        $query = "($query) as {$this->expression->quote($alias)}";

        if(!empty($bindings)) {
            $this->bind($bindings, 'join');
        }

        return $this->join($type, new Expression($query), $column, $on);
    }

    /**
     * Initiate a left join expression.
     *
     * @param string|Expression $table
     * @param string|Closure $column
     * @param string|null $on
     * @return QueryBuilder|BuildsJoins
     */
    public function leftJoin(string|Expression $table, string|Closure $column, string $on = null): self
    {
        return $this->join('left', $table, $column, $on);
    }

    /**
     * Initiate a right join expression.
     *
     * @param string|Expression $table
     * @param string|Closure $column
     * @param string|null $on
     * @return QueryBuilder|BuildsJoins
     */
    public function rightJoin(string|Expression $table, string|Closure $column, string $on = null): self
    {
        return $this->join('right', $table, $column, $on);
    }

    /**
     * Initiate an inner join expression.
     *
     * @param string|Expression $table
     * @param string|Closure $column
     * @param string|null $on
     * @return QueryBuilder|BuildsJoins
     */
    public function innerJoin(string|Expression $table, string|Closure $column, string $on = null): self
    {
        return $this->join('inner', $table, $column, $on);
    }

    /**
     * Initiate a cross join expression.
     *
     * @param string|Expression $table
     * @param string|Closure|null $column
     * @param string|null $on
     * @return QueryBuilder|BuildsJoins
     */
    public function crossJoin(string|Expression $table, string|Closure $column = null, string $on = null): self
    {
        if($column) {
            return $this->join('cross', $table, $column, $on);
        }

        $this->joins[] = new Join('cross', $table);
        return $this;
    }
}