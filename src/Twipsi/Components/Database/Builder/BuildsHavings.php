<?php

namespace Twipsi\Components\Database\Builder;

use Closure;
use Exception;
use Twipsi\Components\Database\Language\Expression;
use Twipsi\Foundation\Exceptions\NotSupportedException;

trait BuildsHavings
{
    /**
     * Add a having clause.
     *
     * @param string|Closure|Expression $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $condition
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function having(string|Closure|Expression $column, string $operator = null, mixed $value = null, string $condition = 'and'): self
    {
        // If column is closure then we will start the nested having
        if($column instanceof Closure && is_null($operator)) {
            return $this->nestHaving($column, $condition);
        }
        
        // If the operator is invalid throw not supported.
        if(! in_array($operator, self::OPERATORS)) {
            throw new NotSupportedException(sprintf("The mysql operator '%s' is not supported", $operator));
        }

        $type = 'Normal';
        $this->havings[] = compact('type', 'column', 'operator', 'value', 'condition');

        $this->bind([$value], 'having');

        return $this;
    }

    /**
     * Make a nested having clause.
     *
     * @param Closure|self $column
     * @param string $condition
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function nestHaving(Closure|self $column, string $condition = 'and'): self
    {
        if($column instanceof Closure) {
            $column($column = $this->new($this->table));
        }

        if(isset($column->havings)) {
            $type = 'Nested';
            $this->havings[] = compact('type', 'column', 'condition');

            var_dump('NESTING HAVING');

            $this->bind($column->bindings['having'], 'having');

        }

        return $this;
    }

    /**
     * Add a having clause with condition (OR) condition.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function orHaving(string|Closure $column, string $operator = null, mixed $value = null): self
    {
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Create a having clause from raw sql.
     *
     * @param string $sql
     * @param array $bindings
     * @param string $condition
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function rawHaving(string $sql, array $bindings = [], string $condition = 'and'): self
    {
        $type = 'Raw';
        $this->havings[] = compact('type', 'sql', 'condition');

        $this->bind($bindings, 'having');

        return $this;
    }

    /**
     * Create a having clause from raw sql with (OR) condition.
     *
     * @param string $sql
     * @param array $bindings
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function orRawHaving(string $sql, array $bindings = []): self
    {
        return $this->rawHaving($sql, $bindings, 'or');
    }

    /**
     * Create having clause having column is null.
     *
     * @param string $column
     * @param string $condition
     * @param string $type
     * @return BuildsHavings|QueryBuilder
     */
    public function havingNull(string $column, string $condition = 'and', string $type = 'Null'): self
    {
        $this->havings[] = compact('type', 'column', 'condition');

        return $this;
    }

    /**
     * Create having is null with (OR) condition.
     *
     * @param string $column
     * @return BuildsHavings|QueryBuilder
     */
    public function orHavingNull(string $column): self
    {
        return $this->havingNull($column, 'or');
    }

    /**
     * Create having clause having column is not null.
     *
     * @param string $column
     * @param string $condition
     * @return BuildsHavings|QueryBuilder
     */
    public function havingNotNull(string $column, string $condition = 'and'): self
    {
        return $this->havingNull($column, $condition, 'NotNull');
    }

    /**
     * Create having is not null with (OR) condition.
     *
     * @param string $column
     * @return BuildsHavings|QueryBuilder
     */
    public function orHavingNotNull(string $column): self
    {
        return $this->havingNotNull($column, 'or');
    }

    /**
     * Create having clause having column is between values.
     *
     * @param string $column
     * @param array $values
     * @param string $condition
     * @param string $type
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function havingBetween(string $column, array $values, string $condition = 'and', string $type = 'Between'): self
    {
        $this->havings[] = compact('type', 'column', 'values', 'condition');

        $this->bind($values, 'having');

        return $this;
    }

    /**
     * Create between having clause with (OR) condition.
     *
     * @param string $column
     * @param array $values
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function orHavingBetween(string $column, array $values): self
    {
        return $this->havingBetween($column, $values, 'or');
    }

    /**
     * Create having clause having column is not between values.
     *
     * @param string $column
     * @param array $values
     * @param string $condition
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function havingNotBetween(string $column, array $values, string $condition = 'and'): self
    {
        return $this->havingBetween($column, $values, $condition, 'NotBetween');
    }

    /**
     * Create not between having clause with (OR) condition.
     *
     * @param string $column
     * @param array $values
     * @return BuildsHavings|QueryBuilder
     * @throws Exception
     */
    public function orHavingNotBetween(string $column, array $values): self
    {
        return $this->havingNotBetween($column, $values, 'or');
    }
}