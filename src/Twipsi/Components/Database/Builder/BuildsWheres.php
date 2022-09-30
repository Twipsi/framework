<?php

namespace Twipsi\Components\Database\Builder;

use Closure;
use DateTimeInterface;
use Exception;
use Twipsi\Components\Database\Language\Expression;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Chronos;

trait BuildsWheres
{
    /**
     * Add a where clause.
     *
     * @param string|Closure|Expression $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function where(string|Closure|Expression $column, string $operator = null, mixed $value = null, string $condition = 'and'): self
    {
        // If column is closure then we will start the nested where
        if($column instanceof Closure && is_null($operator)) {
            return $this->nestWhere($column, $condition);
        }

        // If we have a query as the column, start the sub select where.
        if(!$column instanceof Expression && $this->isQuery($column) && ! is_null($operator)) {
            return $this->makeWhere($column, $operator, $value, $condition);
        }

        // If the operator is invalid throw not supported.
        if(! in_array($operator, self::OPERATORS)) {
            throw new NotSupportedException(sprintf("The mysql operator '%s' is not supported", $operator));
        }

        // If the value is a sub select, build it.
        if ($value instanceof Closure) {
            return $this->subWhere($column, $operator, $value, $condition);
        }

        $type = 'Normal';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $this->bind([$value], 'where');

        return $this;
    }

    /**
     * Create a where clause from a sub select.
     *
     * @param string|self $query
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws NotSupportedException
     * @throws Exception
     */
    public function makeWhere(string|self $query, string $operator = null, mixed $value = null, string $condition = 'and'): self
    {
        [$query, $bindings] = $this->parseExpression($query);

        if(!empty($bindings)) {
            $this->bind($bindings, 'where');
        }

        return $this->where(new Expression("($query)"), $operator, $value, $condition);
    }

    /**
     * Make a nested where clause.
     *
     * @param Closure|self $column
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function nestWhere(Closure|self $column, string $condition = 'and'): self
    {
        if($column instanceof Closure) {
            $column($column = $this->new($this->table));
        }

        $type = 'Nested';
        $this->where[] = compact('type', 'column', 'condition');

        $this->bind($column->bindings['where'], 'where');

        return $this;
    }

    /**
     * Make a where clause where value is sub select.
     *
     * @param string $column
     * @param string $operator
     * @param Closure $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    protected function subWhere(string $column, string $operator, Closure $value, string $condition): self
    {
        $value($value = $this->new($this->table));

        $type = 'Sub';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $this->bind($value->bindings, 'where');

        return $this;
    }

    /**
     * Add a where clause with condition (OR) condition.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhere(string|Closure $column, string $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Create where clause with NOT condition.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereNot(string|Closure $column, string $operator = null, mixed $value = null, string $condition = 'and'): self
    {
        return $this->where($column, $operator, $value, $condition.' not');
    }

    /**
     * Create where clause with NOT condition.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereNot(string|Closure $column, string $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'or not');
    }

    /**
     * Compare two columns in a where clause.
     *
     * @param string $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     */
    public function whereCompare(string $column, string $operator = null, mixed $value = null, string $condition = 'and'): self
    {
        $column = [$column, $value];

        $type = 'Compared';
        $this->where[] = compact('type', 'column', 'operator', 'condition');

        return $this;
    }

    /**
     * Compare two columns in a where clause with (OR) condition.
     *
     * @param string $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return BuildsWheres|QueryBuilder
     */
    public function orWhereCompare(string $column, string $operator = null, mixed $value = null): self
    {
        return $this->whereCompare($column, $operator, $value, 'or');
    }

    /**
     * Create a where clause from raw sql.
     *
     * @param string $sql
     * @param array $bindings
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function rawWhere(string $sql, array $bindings = [], string $condition = 'and'): self
    {
        $type = 'Raw';
        $this->where[] = compact('type', 'sql', 'condition');

        $this->bind($bindings, 'where');

        return $this;
    }

    /**
     * Create a where clause from raw sql with (OR) condition.
     *
     * @param string $sql
     * @param array $bindings
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orRawWhere(string $sql, array $bindings = []): self
    {
        return $this->rawWhere($sql, $bindings, 'or');
    }

    /**
     * Create a where clause with (In).
     *
     * @param string $column
     * @param mixed $values
     * @param string $condition
     * @param string $type
     * @return BuildsWheres|QueryBuilder
     * @throws NotSupportedException
     * @throws Exception
     */
    public function whereIn(string $column, mixed $values, string $condition = 'and', string $type = 'In'): self
    {
        if($this->isQuery($values)) {
            $values = $this->makeWhereIn($column);

        } else {
            $this->bind($values, 'where');
        }

        $this->where[] = compact('type', 'column', 'values', 'condition');

        return $this;
    }

    /**
     * Make a where in value from a sub select.
     *
     * @param string|self $query
     * @return Expression
     * @throws NotSupportedException
     * @throws Exception
     */
    protected function makeWhereIn(string|self $query): Expression
    {
        [$query, $bindings] = $this->parseExpression($query);

        $this->bind($bindings, 'where');

        return new Expression($query);
    }

    /**
     * Create a where in clause with (OR) condition.
     *
     * @param string $column
     * @param mixed $values
     * @return BuildsWheres|QueryBuilder
     * @throws NotSupportedException
     */
    public function orWhereIn(string $column, mixed $values): self
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Create a where clause with (Not In).
     *
     * @param string $column
     * @param mixed $values
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws NotSupportedException
     */
    public function whereNotIn(string $column, mixed $values, string $condition = 'and'): self
    {
        return $this->whereIn($column, $values, $condition, 'NotIn');
    }

    /**
     * Create a where not in clause with (OR) condition.
     *
     * @param string $column
     * @param mixed $values
     * @return BuildsWheres|QueryBuilder
     * @throws NotSupportedException
     */
    public function orWhereNotIn(string $column, mixed $values): self
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Create where clause where column is null.
     *
     * @param string $column
     * @param string $condition
     * @param string $type
     * @return BuildsWheres|QueryBuilder
     */
    public function whereNull(string $column, string $condition = 'and', string $type = 'Null'): self
    {
        $this->where[] = compact('type', 'column', 'condition');

        return $this;
    }

    /**
     * Create where is null with (OR) condition.
     *
     * @param string $column
     * @return BuildsWheres|QueryBuilder
     */
    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Create where clause where column is not null.
     *
     * @param string $column
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     */
    public function whereNotNull(string $column, string $condition = 'and'): self
    {
        return $this->whereNull($column, $condition, 'NotNull');
    }

    /**
     * Create where is not null with (OR) condition.
     *
     * @param string $column
     * @return BuildsWheres|QueryBuilder
     */
    public function orWhereNotNull(string $column): self
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Create where clause where column is between values.
     *
     * @param string $column
     * @param array $values
     * @param string $condition
     * @param string $type
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereBetween(string $column, array $values, string $condition = 'and', string $type = 'Between'): self
    {
        $this->where[] = compact('type', 'column', 'values', 'condition');

        $this->bind($values, 'where');

        return $this;
    }

    /**
     * Create between where clause with (OR) condition.
     *
     * @param string $column
     * @param array $values
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereBetween(string $column, array $values): self
    {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Create where clause where column is not between values.
     *
     * @param string $column
     * @param array $values
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereNotBetween(string $column, array $values, string $condition = 'and'): self
    {
        return $this->whereBetween($column, $values, $condition, 'NotBetween');
    }

    /**
     * Create not between where clause with (OR) condition.
     *
     * @param string $column
     * @param array $values
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereNotBetween(string $column, array $values): self
    {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Create where claus where date(column) = value.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereDate(string $column, string $operator, mixed $value, string $condition = 'and'): self
    {
        $type = 'Date';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $value = match (true) {
            $value instanceof Chronos => $value->getDate(),
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => $value,
        };

        $this->bind([$value], 'where');

        return $this;
    }

    /**
     * Create Date() where with (OR) condition.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereDate(string $column, string $operator, mixed $value): self
    {
        return $this->whereDate($column, $operator, $value, 'or');
    }

    /**
     * Create where claus where time(column) = value.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereTime(string $column, string $operator, mixed $value, string $condition = 'and'): self
    {
        $type = 'Time';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $value = match (true) {
            $value instanceof Chronos => $value->getTime(),
            $value instanceof DateTimeInterface => $value->format('H:i:s'),
            default => $value,
        };

        $this->bind([$value], 'where');

        return $this;
    }

    /**
     * Create Time() where with (OR) condition.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereTime(string $column, string $operator, mixed $value): self
    {
        return $this->whereTime($column, $operator, $value, 'or');
    }

    /**
     * Create where claus where day(column) = value.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereDay(string $column, string $operator, mixed $value, string $condition = 'and'): self
    {
        $type = 'Day';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $value = match (true) {
            $value instanceof Chronos => $value->getDayNumber(),
            $value instanceof DateTimeInterface => $value->format('d'),
            default => $value,
        };

        $this->bind([$value], 'where');

        return $this;
    }

    /**
     * Create Day() where with (OR) condition.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereDay(string $column, string $operator, mixed $value): self
    {
        return $this->whereDay($column, $operator, $value, 'or');
    }

    /**
     * Create where claus where month(column) = value.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereMonth(string $column, string $operator, mixed $value, string $condition = 'and'): self
    {
        $type = 'Month';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $value = match (true) {
            $value instanceof Chronos => $value->getMonthNumber(),
            $value instanceof DateTimeInterface => $value->format('m'),
            default => $value,
        };

        $this->bind([$value], 'where');

        return $this;
    }

    /**
     * Create Month() where with (OR) condition.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereMonth(string $column, string $operator, mixed $value): self
    {
        return $this->whereMonth($column, $operator, $value, 'or');
    }

    /**
     * Create where claus where year(column) = value.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereYear(string $column, string $operator, mixed $value, string $condition = 'and'): self
    {
        $type = 'Year';
        $this->where[] = compact('type', 'column', 'operator', 'value', 'condition');

        $value = match (true) {
            $value instanceof Chronos => $value->getYear(),
            $value instanceof DateTimeInterface => $value->format('Y'),
            default => $value,
        };

        $this->bind([$value], 'where');

        return $this;
    }

    /**
     * Create Year() where with (OR) condition.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereYear(string $column, string $operator, mixed $value): self
    {
        return $this->whereYear($column, $operator, $value, 'or');
    }

    /**
     * Add an exists where clause.
     *
     * @param Closure $column
     * @param string $condition
     * @param string $type
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereExists(Closure $column, string $condition = 'and', string $type = 'Exists'): self
    {
        $column($column = $this->new($this->table));

        $this->where[] = compact('type', 'column', 'condition');

        $this->bind($column->bindings, 'where');

        return $this;
    }

    /**
     * Add an exists where clause with (OR) condition.
     *
     * @param Closure $column
     * @param string $type
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereExists(Closure $column, string $type = 'Exists'): self
    {
        return $this->whereExists($column, 'or', $type);
    }

    /**
     * Add a not exists where clause.
     *
     * @param Closure $column
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereNotExists(Closure $column, string $condition = 'and'): self
    {
        return $this->whereExists($column, $condition, 'NotExists');
    }

    /**
     *  Add a not exists where clause with (OR) condition.
     *
     * @param Closure $column
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereNotExists(Closure $column): self
    {
        return $this->whereNotExists($column, 'or');
    }

    /**
     * Make where clause with rowed value comparison.
     *
     * @param array $columns
     * @param string $operator
     * @param array $values
     * @param string $condition
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function whereRowedValues(array $columns, string $operator, array $values, string $condition = 'and'): self
    {
        $type = 'Rowed';
        $this->where[] = compact('type', 'columns', 'operator', 'values', 'condition');

        $this->bind($values, 'where');

        return $this;
    }

    /**
     * Make rowed value comparison where with (OR) condition.
     *
     * @param array $columns
     * @param string $operator
     * @param array $values
     * @return BuildsWheres|QueryBuilder
     * @throws Exception
     */
    public function orWhereRowedValues(array $columns, string $operator, array $values): self
    {
        return $this->whereRowedValues($columns, $operator, $values, 'or');
    }
}