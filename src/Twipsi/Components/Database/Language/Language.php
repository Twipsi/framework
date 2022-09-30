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

namespace Twipsi\Components\Database\Language;

use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Str;

abstract class Language
{
    /**
     * Table prefix to use.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Interchangeable properties.
     *
     * @var array|string[]
     */
    protected array $interchange = [
        'alias'  => 'table',
        'values' => 'columns',
        'updateColumns' => 'columns',
        'insertColumns' => 'bindings',
    ];

    /**
     * Get the statement scheme map to build.
     *
     * @param string $statement
     * @return string
     * @throws NotSupportedException
     */
    public function getScheme(string $statement): string
    {
        return match ($statement) {
            'insert' => /** @lang text */ "insert into {table} {insertColumns} {values}",
            'select' => /** @lang text */ "select {aggregate} {columns} from {table} {joins} {where} {groups} {havings} {orders} {limit} {offset}",
            'update' => /** @lang text */ "update {table} {join} set {updateColumns} {where}",
            'delete' => /** @lang text */ "delete {alias} from {table} {join} {where}",
            default => throw new NotSupportedException(sprintf("Statement '%s' not supported", $statement)),
        };
    }

    /**
     * Dynamically build the requested expression.
     *
     * @param QueryBuilder $query
     * @param string|null $statement
     * @return string
     * @throws NotSupportedException
     */
    public function toExpression(QueryBuilder $query, string $statement = null): string
    {
        $scheme = $this->getScheme($statement ?? $query->statement);

        foreach(Str::hay($scheme)->between('{', '}') as $placeholder) {

            if( isset($query->$placeholder)
                || isset($query->{$this->interchange[$placeholder] ?? null})) {

                $method = 'to'.ucfirst($placeholder);

                $scheme = Str::hay($scheme)
                    ->replace("{{$placeholder}}", $this->$method($query) ?? '');
            } else {
                $scheme = Str::hay($scheme)->replace(" {{$placeholder}}", '');
            }
        }

        return $scheme;
    }

    /**
     * Select while checking existence.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toExists(QueryBuilder $query): string
    {
        return "select exists({$this->toSelect($query)}) as {$this->quote('exists')}";
    }

    /**
     * Build an insert statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toInsert(QueryBuilder $query): string
    {
        $scheme = $this->toExpression($query, 'insert');

        return $scheme;
    }

    /**
     * Build an insert statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toInsertIgnore(QueryBuilder $query): string
    {
        return '';
    }

    /**
     * Build an upsert statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toUpsert(QueryBuilder $query): string
    {
        return '';
    }

    /**
     * Build a select statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toSelect(QueryBuilder $query): string
    {
        $query->columns = $query->columns ?? ['*'];
        $scheme = $this->toExpression($query, 'select');

        return $scheme;
    }

    /**
     * Build an update statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toUpdate(QueryBuilder $query): string
    {
        $scheme = $this->toExpression($query, 'update');
        return $scheme;
    }

    /**
     * Build a delete statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toDelete(QueryBuilder $query): string
    {
        $scheme = $this->toExpression($query, 'delete');
        return $scheme;
    }

    /**
     * Build an aggregate statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toAggregate(QueryBuilder $query): string
    {
        $column = $this->columnize($query->aggregate['column']);

        // If we have any distinct values append it to the statement.
        if($query->distinct) {
            $column = $this->makeDistinct($query, $column);
        }

        return "{$query->aggregate['function']}($column)";
    }

    /**
     * Build the columns of a select statement.
     *
     * @param QueryBuilder $query
     * @return string|null
     */
    public function toColumns(QueryBuilder $query): ?string
    {
        // IF we are aggregating then return.
        if(isset($query->aggregate)) {
            return null;
        }

        // If we have distinct set, append it to the statement.
        return $query->distinct
            ? $this->makeDistinct($query, $this->columnize($query->columns))
            : $this->columnize($query->columns);
    }

    /**
     * Build the columns of an update statement.
     *
     * @param QueryBuilder $query
     * @return string|null
     */
    public function toUpdateColumns(QueryBuilder $query): ?string
    {
        $values = $query->bindings['values'];

        $columns = array_map(function($k, $column) use ($values) {

            $value = $values[$k] instanceof Expression
                ? $values[$k]->getExpression()
                : $this->tokenize($column);

            return "{$this->quote($column)} = {$value}";
        }, array_keys($query->columns), $query->columns);

        return implode(', ', $columns);
    }

    /**
     * Build the columns of an update statement.
     *
     * @param QueryBuilder $query
     * @return string|null
     */
    public function toInsertColumns(QueryBuilder $query): ?string
    {
        return "({$this->columnize($query->columns)})";
    }

    /**
     * Build the values of an insert statement.
     *
     * @param QueryBuilder $query
     * @return string|null
     */
    public function toValues(QueryBuilder $query): ?string
    {
        if(empty($query->bindings)) {
            return 'default values';
        }

        return "values ({$this->tokenize($query->columns)})";
    }

    /**
     * Build the (from) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toTable(QueryBuilder $query): string
    {
        return $this->quoteTable($query->table);
    }

    /**
     * Build the (alias) of a delete statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toAlias(QueryBuilder $query): string
    {
        $table = $query->table instanceof Expression
            ? $query->table->getExpression()
            : $query->table;

        if(count($alias = explode(' as ', $table)) <= 1) {
            return '';
        }

        return $this->quoteTable(end($alias));
    }

    /**
     * Build the (joins) of a select statement.
     *
     * @param QueryBuilder $query
     * @param array|null $joins
     * @return string
     */
    public function toJoins(QueryBuilder $query, array $joins = null): string
    {
        foreach($joins ?? $query->joins as $join) {

            $table = $this->quoteTable($join->table);

            $nested = ! empty($join->joins)
                ? "($table {$this->toJoins($query, $join->joins)})"
                : $table;

            $compiled[] = trim("{$join->type} join $nested {$this->compileJoin($join)}");
        }

        return implode(' ', $compiled ?? []);
    }

    /**
     * Compile join columns.
     *
     * @param Join $join
     * @return string
     */
    protected function compileJoin(Join $join): string
    {
        return !empty($join->columns)
            ? 'on '.implode($join->columns)
            : '';
    }

    /**
     * Build the (where) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toWhere(QueryBuilder $query): string
    {
        // If we don't have any sett just return empty.
        if(!isset($query->where)) {
            return '';
        }

        // Compile all the where parts.
        return !empty( $compiled = $this->makeWheres($query))
            ? $this->compileWheres($compiled)
            : '';
    }

    /**
     * Make the where parts of the statement.
     *
     * @param QueryBuilder $query
     * @return array
     */
    protected function makeWheres(QueryBuilder $query): array
    {
        return array_map(function($key, $where) {

            $condition = $key == 0 ? '' : $where['condition'].' ';
            return $condition.$this->{"make{$where['type']}Where"}($where);

        }, array_keys($query->where), $query->where);
    }

    /**
     * Compile the where parts into a string.
     *
     * @param array $wheres
     * @return string
     */
    protected function compileWheres(array $wheres): string
    {
        return 'where '.implode(' ', $wheres);
    }

    /**
     * Build the (groups) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toGroups(QueryBuilder $query): string
    {
        return "group by {$this->columnize($query->groups)}";
    }

    /**
     * Build the (havings) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toHavings(QueryBuilder $query): string
    {
        // If we don't have any sett just return empty.
        if(!isset($query->havings)) {
            return '';
        }

        // Compile all the having parts.
        return !empty( $compiled = $this->makeHavings($query))
            ? 'having '. implode(' ', $compiled)
            : '';
    }

    /**
     * Make the having parts of the statement.
     *
     * @param QueryBuilder $query
     * @return array
     */
    protected function makeHavings(QueryBuilder $query): array
    {
        return array_map(function($key, $having) {

            $condition = $key == 0 ? '' : $having['condition'].' ';
            return $condition.$this->{"make{$having['type']}Having"}($having);
        }, array_keys($query->havings), $query->havings);
    }

    /**
     * Build the (orders) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toOrders(QueryBuilder $query): string
    {
        // Compile all the orders parts.
        return !empty( $compiled = $this->makeOrders($query))
            ? 'order by '.implode(', ', $compiled)
            : '';
    }

    /**
     * Make the orders parts of the statement.
     *
     * @param QueryBuilder $query
     * @return array
     */
    protected function makeOrders(QueryBuilder $query): array
    {
        return array_map(function($order) {

            if(isset($order['sql'])) {
                return "({$this->quote($order['sql'])})";
            }

            return "{$this->quote($order['column'])} {$order['direction']}";
        }, $query->orders);
    }

    /**
     * Build the (limit) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toLimit(QueryBuilder $query): string
    {
        return "limit {$query->limit}";
    }

    /**
     * Build the (offset) of a select statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toOffset(QueryBuilder $query): string
    {
        return "offset {$query->offset}";
    }

    /**
     * Build the (unions) of a statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toUnions(QueryBuilder $query): string
    {
        // To implement later
        return '';
    }

    /**
     * Make the partial distinct.
     *
     * @param QueryBuilder $query
     * @param string $partial
     * @return string
     */
    protected function makeDistinct(QueryBuilder $query, string $partial): string
    {
        // If we have an array of specified distincts
        // replace the original columns with them.
        if(is_array($query->distinct)) {
            return "distinct {$this->columnize($query->distinct)}";
        }

        // If we have distinct set to true, and we are not
        // querying all the columns just prepend it.
        return $query->distinct && $partial !== '*'
            ? "distinct $partial"
            : $partial;
    }

    /**
     * Make a raw sql where.
     *
     * @param array $where
     * @return string
     */
    protected function makeRawWhere(array $where): string
    {
        return $where['sql'];
    }

    /**
     * Make a normal where.
     *
     * @param array $where
     * @return string
     */
    protected function makeNormalWhere(array $where): string
    {
        return "{$this->quote($where['column'])} {$where['operator']} {$this->tokenize($where['value'])}";
    }

    /**
     * Make a where IN.
     *
     * @param array $where
     * @return string
     */
    protected function makeInWhere(array $where): string
    {
        return "{$this->quote($where['column'])} in ({$this->tokenize($where['values'])})";
    }

    /**
     * Make a where NOT IN.
     *
     * @param array $where
     * @return string
     */
    protected function makeNotInWhere(array $where): string
    {
        return "{$this->quote($where['column'])} not in ({$this->tokenize($where['values'])})";
    }

    /**
     * Make a NULL where.
     *
     * @param array $where
     * @return string
     */
    protected function makeNullWhere(array $where): string
    {
        return "{$this->quote($where['column'])} is null";
    }

    /**
     * Make a NOT NULL where.
     *
     * @param array $where
     * @return string
     */
    protected function makeNotNullWhere(array $where): string
    {
        return "{$this->quote($where['column'])} is not null";
    }

    /**
     * Make a Between where.
     *
     * @param array $where
     * @return string
     */
    protected function makeBetweenWhere(array $where): string
    {
        return "({$this->quote($where['column'])} between {$this->tokenize($where['values'][0])} and {$this->tokenize($where['values'][1])})";
    }

    /**
     * Make a Not Between where.
     *
     * @param array $where
     * @return string
     */
    protected function makeNotBetweenWhere(array $where): string
    {
        return "({$this->quote($where['column'])} not between {$this->tokenize($where['values'][0])} and {$this->tokenize($where['values'][1])})";
    }

    /**
     * Make a date where.
     *
     * @param array $where
     * @return string
     */
    protected function makeDateWhere(array $where): string
    {
        return "date({$this->quote($where['column'])}) {$where['operator']} {$this->tokenize($where['value'])}";
    }

    /**
     * Make a (time) where.
     *
     * @param array $where
     * @return string
     */
    protected function makeTimeWhere(array $where): string
    {
        return "time({$this->quote($where['column'])}) {$where['operator']} {$this->tokenize($where['value'])}";
    }

    /**
     * Make a (day) where.
     *
     * @param array $where
     * @return string
     */
    protected function makeDayWhere(array $where): string
    {
        return "day({$this->quote($where['column'])}) {$where['operator']} {$this->tokenize($where['value'])}";
    }

    /**
     * Make a (month) where.
     *
     * @param array $where
     * @return string
     */
    protected function makeMonthWhere(array $where): string
    {
        return "month({$this->quote($where['column'])}) {$where['operator']} {$this->tokenize($where['value'])}";
    }

    /**
     * Make a (year) where.
     *
     * @param array $where
     * @return string
     */
    protected function makeYearWhere(array $where): string
    {
        return "year({$this->quote($where['column'])}) {$where['operator']} {$this->tokenize($where['value'])}";
    }

    /**
     * Make a compared where.
     *
     * @param array $where
     * @return string
     */
    protected function makeComparedWhere(array $where): string
    {
        return "{$this->quote($where['column'][0])} {$where['operator']} {$this->quote($where['column'][1])}";
    }

    /**
     * Make a nested where.
     *
     * @param array $where
     * @return string
     */
    protected function makeNestedWhere(array $where): string
    {
        $sub = str_replace('where ', '', $this->toWhere($where['column']));
        return "($sub)";
    }

    /**
     * Make an exists where.
     *
     * @param array $where
     * @return string
     * @throws NotSupportedException
     */
    protected function makeExistsWhere(array $where): string
    {
        return "exists ({$this->toSelect($where['column'])})";
    }

    /**
     * Make a not exists where.
     *
     * @param array $where
     * @return string
     * @throws NotSupportedException
     */
    protected function makeNotExistsWhere(array $where): string
    {
        return "not exists ({$this->toSelect($where['column'])})";
    }

    /**
     * Make a sub select where.
     *
     * @param array $where
     * @return string
     * @throws NotSupportedException
     */
    protected function makeSubWhere(array $where): string
    {
        return "{$this->quote($where['column'])} {$where['operator']} ({$this->toSelect($where['value'])})";
    }

    /**
     * Make a rowed where.
     *
     * @param array $where
     * @return string
     */
    protected function makeRowedWhere(array $where): string
    {
        return "({$this->columnize($where['columns'])}) {$where['operator']} ({$this->tokenize($where['values'])})";
    }

    /**
     * Make a raw sql having.
     *
     * @param array $having
     * @return string
     */
    protected function makeRawHaving(array $having): string
    {
        return $having['sql'];
    }

    /**
     * Make a normal having.
     *
     * @param array $having
     * @return string
     */
    protected function makeNormalHaving(array $having): string
    {
        return "{$this->quote($having['column'])} {$having['operator']} {$this->tokenize($having['value'])}";
    }

    /**
     * Make a NULL having.
     *
     * @param array $having
     * @return string
     */
    protected function makeNullHaving(array $having): string
    {
        return "{$this->quote($having['column'])} is null";
    }

    /**
     * Make a NOT NULL having.
     *
     * @param array $having
     * @return string
     */
    protected function makeNotNullHaving(array $having): string
    {
        return "{$this->quote($having['column'])} is not null";
    }

    /**
     * Make a Between having.
     *
     * @param array $having
     * @return string
     */
    protected function makeBetweenHaving(array $having): string
    {
        return "{$this->quote($having['column'])} between {$this->tokenize($having['values'][0])} and {$this->tokenize($having['values'][1])}";
    }

    /**
     * Make a Not Between having.
     *
     * @param array $having
     * @return string
     */
    protected function makeNotBetweenHaving(array $having): string
    {
        return "{$this->quote($having['column'])} not between {$this->tokenize($having['values'][0])} and {$this->tokenize($having['values'][1])}";
    }

    /**
     * Make a nested having.
     *
     * @param array $having
     * @return string
     */
    protected function makeNestedHaving(array $having): string
    {
        return '('.substr($this->toHavings($having['column']), 7).')';
    }

    /**
     * Build a save point for the transaction.
     *
     * @param int $level
     * @return string
     */
    public function buildRegisterSavePointExpression(int $level): string
    {
        return "SAVEPOINT LEVEL{$level}";
    }

    /**
     * Build a release point for the transaction.
     *
     * @param int $level
     * @return string
     */
    public function buildReleaseSavePointExpression(int $level): string
    {
        return "RELEASE SAVEPOINT LEVEL{$level}";
    }

    /**
     * Build a rollback point for the transaction.
     *
     * @param int $level
     * @return string
     */
    public function buildRollbackSavePointExpression(int $level): string
    {
        return "ROLLBACK TO SAVEPOINT LEVEL{$level}";
    }

    /**
     * Check if the driver supports save points.
     *
     * @return bool
     */
    public function supportsSavePoints(): bool
    {
        return false;
    }

    /**
     * Prefix and quote a table name.
     *
     * @param string|Expression $table
     * @return string
     */
    public function quoteTable(string|Expression $table): string
    {
        if($table instanceof Expression) {
            return $table->getExpression();
        }

        return $this->quote($this->prefix.$table);
    }

    /**
     * Dynamically quote a value.
     *
     * @param string|Expression $value
     * @return string
     */
    public function quote(string|Expression $value): string
    {
        if($value instanceof Expression) {
            return $value->getExpression();
        }

        if(Str::hay($value)->has(' as ')) {
            return $this->quoteAliasValue($value);
        }

        if(count($segments = explode('.', $value)) > 1) {
            return $this->quoteTable($segments[0]).'.'.$this->quoteValue($segments[1]);
        }

        return $this->quoteValue($value);
    }

    /**
     * Quote an aliased value.
     *
     * @param string $value
     * @return string
     */
    protected function quoteAliasValue(string $value): string
    {
        [$before, $after]
            = [Str::hay($value)->beforeLast('as'), Str::hay($value)->afterLast('as')];

        return $this->quote(trim($before)).' as '.$this->quoteValue($this->prefix.trim($after));
    }

    /**
     * Wrap a value in quotes.
     *
     * @param string $value
     * @return string
     */
    protected function quoteValue(string $value): string
    {
        return ($value !== '*')
            ? '"'.str_replace('"', '""', $value).'"'
            : $value;
    }

    /**
     * Wrap a value(s) in quotes.
     *
     * @param string|array $value
     * @return string
     */
    protected function quoteString(string|array $value): string
    {
        return is_array($value)
            ? implode(', ', array_map([$this, 'quoteValue'], $value))
            : "'$value'";
    }

    /**
     * PDO tokenize parameters.
     *
     * @param mixed $parameter
     * @return string
     */
    public function tokenize(mixed $parameter): string
    {
        return is_array($parameter)
            ? implode(', ', array_map([$this, 'tokenize'], $parameter))
            : '?';
    }

    /**
     * Quote the columns.
     *
     * @param string|array $columns
     * @return string
     */
    public function columnize(string|array $columns): string
    {
        return is_array($columns)
            ? implode(', ', array_map([$this, 'quote'], $columns))
            : $this->quote($columns);
    }

    /**
     * Set the table prefix.
     *
     * @param string $prefix
     * @return Language
     */
    public function setPrefix(string $prefix): Language
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the table prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix ?? '';
    }
}
