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

namespace Twipsi\Components\Database\Builder;

use Exception;
use Generator;
use PDO;
use Twipsi\Components\Database\Language\Expression;
use Twipsi\Components\Database\Language\Language;
use Twipsi\Components\Database\QueryDispatcher;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Arr;
use Twipsi\Support\Bags\RecursiveArrayBag;
use Twipsi\Support\Str;

final class QueryBuilder
{
    use BuildsWheres, BuildsJoins, BuildsHavings, BuildsPagination;

    /**
     * Available sql statements.
     */
    protected const STATEMENTS = [
        'select', 'update', 'insert', 'delete', 'alter', 'create', 'drop', 'set',
        'rename', 'truncate', 'import', 'load', 'values',
    ];

    /**
     * Statements that can have bindings.
     */
    protected const BINDABLES = [
        'select', 'values', 'table', 'join', 'where', 'having', 'order',
        'groupBy', 'union', 'union order',
    ];

    /**
     * Available operators.
     */
    protected const OPERATORS = [
        '<=', '>=', '<>', '!=', '<=>', '=', '<', '>',
        'like binary', 'not like', 'ilike', 'like',
        'not ilike', 'not similar to', 'similar to',
        'is not', 'is', 'not rlike', 'rlike',
        'not regexp', 'regexp', '!~~*', '~~*',
        '|', '^', '<<', '>>', '&~', '&',
        '!~*', '~*', '!~', '~',
    ];

    /**
     * The default id column name.
     */
    protected const IDCOLUMN = 'id';

    /**
     * The database connection.
     *
     * @var QueryDispatcher
     */
    protected QueryDispatcher $dispatcher;

    /**
     * The expression language builder.
     *
     * @var Language
     */
    protected Language $expression;

    /**
     * The main statement we are building.
     *
     * @var string
     */
    public string $statement;

    /**
     * The db table we are working on.
     *
     * @var string|Expression
     */
    public string|Expression $table;

    /**
     * Weather we should use distinct.
     * It can contain the columns as well.
     *
     * @var array|bool
     */
    public array|bool $distinct = false;

    /**
     * The aggregate type and columns.
     * [function => 'MAX', columns => ...]
     *
     * @var array
     */
    public array $aggregate;

    /**
     * The columns in hand.
     *
     * @var array
     */
    public array $columns;

    /**
     * Table joins.
     *
     * @var array
     */
    public array $joins;

    /**
     * The where's of the statement.
     *
     * @var array
     */
    public array $where;

    /**
     * The groupings of the statement.
     *
     * @var array
     */
    public array $groups;

    /**
     * The havings of the statement.
     *
     * @var array
     */
    public array $havings;

    /**
     * The orders of the statement.
     *
     * @var array
     */
    public array $orders;

    /**
     * The limit of the statement.
     * Number of rows to get.
     *
     * @var int
     */
    public int $limit;

    /**
     * THe offset of the statement.
     * Number of rows to skip.
     *
     * @var int
     */
    public int $offset;

    /**
     * The token-value bindings.
     *
     * @var array
     */
    public array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'values' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
        'union' => [],
        'unionOrder' => [],
    ];

    /**
     * Set the table we are working on.
     *
     * @param string|QueryBuilder $table
     * @param string|null $alias
     * @return $this
     * @throws Exception
     */
    public function table(string|QueryBuilder $table, string $alias = null): QueryBuilder
    {
        // If the table is a query, and it should be build
        // based on an expression, build it and set the bindings.
        if ($this->isQuery($table)) {
            return $this->makeTable($table, $alias);
        }

        $this->table = $alias ? "$table as $alias" : $table;

        return $this;
    }

    /**
     * Build an expression based table from a query.
     *
     * @param string|QueryBuilder $table
     * @param string|null $alias
     * @return $this
     * @throws Exception
     */
    public function makeTable(string|QueryBuilder $table, string $alias = null): QueryBuilder
    {
        var_dump('MAKING TABLE');

        [$query, $bindings] = $this->parseExpression($table);

        $query = !is_null($alias) ? "($query) as {$this->expression->quote($alias)}" : "($query)";

        return $this->tableRaw($query, $bindings);
    }

    /**
     * Make a new raw select expression.
     *
     * @param string $table
     * @param array $bindings
     * @return $this
     * @throws Exception
     */
    public function tableRaw(string $table, array $bindings = []): QueryBuilder
    {
        $this->table = new Expression($table);

        if(!empty($bindings)) {
            $this->bind($bindings, 'table');
        }

        return $this;
    }

    /**
     * Set columns to be selected distinctively.
     *
     * @param bool|string $column
     * @param string ...$columns
     * @return $this
     */
    public function distinct(bool|string $column = true, string ...$columns): QueryBuilder
    {
        $this->distinct = !empty($columns) || is_string($column)
            ? array_merge([$column], $columns)
            : $column;

        return $this;
    }

    /**
     * Set aggregation function on select columns.
     *
     * @param string $function
     * @param string $column
     * @param string $alias
     * @return QueryBuilder
     */
    public function aggregate(string $function, string $alias = 'aggregate', string $column = '*'): QueryBuilder
    {
        $this->aggregate = compact('function', 'column', 'alias');

        return $this;
    }

    /**
     * Initialize a select statement.
     * ex. [alias => contact_name]
     * ex. [alias => Select ID from contact_relation WHERE ...]
     *
     * @param array|string ...$columns
     * @return $this
     * @throws Exception
     */
    public function select(array|string ...$columns): QueryBuilder
    {
        [$this->statement, $this->columns] = ['select', []];
        $this->bindings['select'] = [];

        return empty($columns)
            ? $this->addSelect('*')
            : $this->addSelect(...$columns);
    }

    /**
     * Add new select columns to the query.
     *
     * @param array|string|Expression|QueryBuilder ...$columns
     * @return $this
     * @throws Exception
     */
    public function addSelect(array|string|Expression|QueryBuilder ...$columns): QueryBuilder
    {
        foreach (Arr::hay($columns)->flatten() as $alias => $column) {

            // If we have a sub query in the select statement
            // register the query to be executed.
            if ($this->isQuery($column) && is_string($alias)) {

                $this->makeSubSelect($column, $alias);
                continue;
            }

            is_string($alias)
                ? $this->columns[] = "$column as $alias"
                : $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * Build a sub select expression.
     *
     * @param string|QueryBuilder $query
     * @param string|null $alias
     * @return $this
     * @throws Exception
     */
    public function makeSubSelect(string|QueryBuilder $query, string $alias = null): QueryBuilder
    {
        [$query, $bindings] =  $this->parseExpression($query);

        return $this->selectRaw(
            "($query) as {$this->expression->quote($alias)}",
            $bindings
        );
    }

    /**
     * Make a new raw select expression.
     *
     * @param string $query
     * @param array $bindings
     * @return $this
     * @throws Exception
     */
    public function selectRaw(string $query, array $bindings = []): QueryBuilder
    {
        $this->addSelect(new Expression($query));

        if(!empty($bindings)) {
            $this->bind($bindings, 'select');
        }

        return $this;
    }

    /**
     * Make group by clause.
     *
     * @param string ...$columns
     * @return $this
     */
    public function group(string ...$columns): QueryBuilder
    {
        $this->groups = array_merge($this->groups ?? [], $columns);

        return $this;
    }

    /**
     * Make group by clause raw mode.
     *
     * @param string $query
     * @param array $bindings
     * @return $this
     * @throws Exception
     */
    public function rawGroup(string $query, array $bindings = []): QueryBuilder
    {
        $this->groups[] = new Expression($query);

        $this->bind($bindings, 'groupBy');

        return $this;
    }

    /**
     * Make the order by clause.
     *
     * @param string|Expression|QueryBuilder $column
     * @param string $direction
     * @return $this
     * @throws NotSupportedException
     */
    public function order(string|Expression|QueryBuilder $column, string $direction = 'asc'): QueryBuilder
    {
        // If the column is a sub query.
        if(!$column instanceof Expression && $this->isQuery($column)) {
            return $this->makeOrder($column, $direction);
        }

        if(!in_array(strtolower($direction), ['asc', 'desc'], true)) {
            throw new NotSupportedException(sprintf(
                "The provided (Order By) direction '%s' is not supported", $direction
            ));
        }

        $this->orders[$column] = compact('column', 'direction');

        return $this;
    }

    /**
     * Make order by from sub query.
     *
     * @param string|QueryBuilder $query
     * @param string $direction
     * @return $this
     * @throws NotSupportedException
     * @throws Exception
     */
    public function makeOrder(string|QueryBuilder $query, string $direction = 'asc'): QueryBuilder
    {
        [$query, $bindings] = $this->parseExpression($query);

        if(!empty($bindings)) {
            $this->bind($bindings, 'order');
        }

        return $this->order(new Expression("($query)"), $direction);
    }

    /**
     * Make the order by clause with (desc) direction.
     *
     * @param string|Expression|QueryBuilder $column
     * @return $this
     * @throws NotSupportedException
     */
    public function orderDesc(string|Expression|QueryBuilder $column): QueryBuilder
    {
        return $this->order($column, 'desc');
    }

    /**
     * Create order by clause from raw sql.
     *
     * @param string $sql
     * @param array $bindings
     * @return $this
     * @throws Exception
     */
    public function rawOrder(string $sql, array $bindings = []): QueryBuilder
    {
        $type = 'Raw';
        $this->orders[] = compact('type', 'sql');

        $this->bind($bindings, 'order');

        return $this;
    }

    /**
     * Automatically order for the newest row.
     *
     * @param string|Expression|QueryBuilder $column
     * @return $this
     * @throws NotSupportedException
     */
    public function newest(string|Expression|QueryBuilder $column = 'created_at'): QueryBuilder
    {
        return $this->order($column, 'desc');
    }

    /**
     * Automatically order for the oldest row.
     *
     * @param string|Expression|QueryBuilder $column
     * @return $this
     * @throws NotSupportedException
     */
    public function oldest(string|Expression|QueryBuilder $column = 'created_at'): QueryBuilder
    {
        return $this->order($column);
    }

    /**
     * Create limit value.
     *
     * @param int $value
     * @return $this
     */
    public function limit(int $value): QueryBuilder
    {
        $this->limit = max(1, $value);

        return $this;
    }

    /**
     * Create offset value.
     *
     * @param int $value
     * @return $this
     */
    public function offset(int $value): QueryBuilder
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Add binding pairs to a specific statement.
     *
     * @param array $bindings
     * @param string $statement
     * @return $this
     * @throws Exception
     */
    public function bind(array $bindings, string $statement): QueryBuilder
    {
        if (!in_array($statement, self::BINDABLES)) {
            throw new Exception(sprintf("Invalid bindable statement [%s]", $statement));
        }

        $this->bindings[$statement] = isset($this->bindings[$statement])
            ? array_merge($this->bindings[$statement], $bindings)
            : $bindings;

        return $this;
    }

    /**
     * Return the flattened bindings.
     *
     * @param array $bindings
     * @return array
     */
    public function flattenBindings(array $bindings = []): array
    {
        $bindings = !empty($bindings) ? $bindings : $this->bindings;

        $flat = Arr::hay($bindings)->flatten();

        return array_values(array_filter($flat, function($value) {
            return !$value instanceof Expression;
        }));
    }

    /**
     * Parse the expression based on type.
     *
     * @param string|QueryBuilder $query
     * @return array
     * @throws NotSupportedException
     */
    protected function parseExpression(string|QueryBuilder $query): array
    {
        if(is_string($query)) {
            return [$query, []];
        }

        return [$query->toSql(), $query->bindings];
    }

    /**
     * Get the current query as a compiled sql.
     *
     * @return string
     * @throws NotSupportedException
     */
    public function toSql(): string
    {
        if(! isset($this->statement)) {
            return $this->expression->toExpression($this, 'select');
        }

        return $this->expression->toExpression($this);
    }

    /**
     * Check if an expression is a valid query.
     *
     * @param mixed $expression
     * @return bool
     */
    protected function isQuery(mixed $expression): bool
    {
        $statements = array_map('mb_strtoupper', self::STATEMENTS);

        if ($expression instanceof Expression || $expression instanceof QueryBuilder) {
            return true;
        }

        if(is_string($expression)) {
            foreach ($statements as $statement) {
                if(str_starts_with($expression, $statement)) {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Initiate a select statement (by specifying an id)
     *
     * @param int|string $id
     * @param string ...$columns
     * @return object|bool
     * @throws NotSupportedException
     * @throws ApplicationManagerException
     * @throws Exception
     */
    public function find(int|string $id, string ...$columns): object|bool
    {
        return $this->where(self::IDCOLUMN, '=', $id)
            ->first(...(empty($columns) ? ['*'] : $columns));
    }

    /**
     * Get the value of a single column.
     *
     * @param string $column
     * @return mixed
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function column(string $column): mixed
    {
        return count($result = (array)$this->first($column))
            ? reset($result)
            : null;
    }

    /**
     *  Get the min aggregation of the query.
     *
     * @param string $column
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function min(string $column = '*'): mixed
    {
        return $this->cloneWithout('columns')
            ->aggregate(__FUNCTION__, 'min', $column)
            ->column('min');
    }

    /**
     *  Get the max aggregation of the query.
     *
     * @param string $column
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function max(string $column = '*'): mixed
    {
        return $this->cloneWithout('columns')
            ->aggregate(__FUNCTION__, 'max', $column)
            ->column('max');
    }

    /**
     *  Get the count aggregation of the query.
     *
     * @param string $column
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function count(string $column = '*'): int
    {
        $count = $this->cloneWithout('columns')
            ->aggregate(__FUNCTION__, 'count', $column)
            ->column('total');

        return (int) $count;
    }

    /**
     *  Get the avg aggregation of the query.
     *
     * @param string $column
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function avg(string $column = '*'): mixed
    {
        return $this->cloneWithout('columns')
            ->aggregate(__FUNCTION__, 'avg', $column)
            ->column('avg');
    }

    /**
     *  Get the sum aggregation of the query.
     *
     * @param string $column
     * @return mixed
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function sum(string $column = '*'): mixed
    {
        $sum = $this->cloneWithout('columns')
            ->aggregate(__FUNCTION__, 'sum', $column)
            ->column('sum');

        return $sum ?: 0;
    }

    /**
     * Initiate insert statement with column-value pairs.
     *
     * @param array $colval
     * @return bool
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     * @throws Exception
     */
    public function insert(array $colval): bool
    {
        if(empty($colval)) {
            return true;
        }

        $this->columns = array_keys($colval);
        $this->bind(array_values($colval), 'values');

        return $this->dispatcher->insert(
            $this->expression->toInsert($this),
            $this->flattenBindings($this->bindings['values'])
        );
    }

    /**
     * Initiate insert ignore statement with column-value pairs.
     *
     * @param array $colval
     * @return int
     * @throws ApplicationManagerException
     * @throws Exception
     */
    public function insertIgnore(array $colval): int
    {
        if(empty($colval)) {
            return 0;
        }

        $this->columns = array_keys($colval);
        $this->bind(array_values($colval), 'values');

        return $this->dispatcher->affect(
            $this->expression->toInsertIgnore($this),
            $this->flattenBindings($this->bindings['values'])
        );
    }

    /**
     * Insert while retrieving the last inserted id.
     *
     * @param array $colval
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function insertGetId(array $colval): int
    {
        $this->insert($colval);

        return (int)$this->dispatcher->lastInserted();
    }

    /**
     * Initiate an update statement (by specifying columns and values)
     *
     * @param array $colval
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     * @throws Exception
     */
    public function update(array $colval): int
    {
        if(empty($colval)) {
            return 0;
        }

        $this->columns = array_keys($colval);
        $this->bind(array_values($colval), 'values');

        return $this->dispatcher->update(
            $this->expression->toUpdate($this),
            $this->flattenBindings()
        );
    }

    /**
     * Initiate an update statement that inserts in case of false return.
     *
     * @param array $colval
     * @return int|bool
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function updateOrCreate(array $colval): int|bool
    {
        if(empty($colval)) {
            return 0;
        }

        // If the select where returns no result.
        if(!$this->exists()) {

            // Set the where column on the insert column.
            foreach ($this->where as $where) {
                if($where['operator'] === '=') {
                    $colval[$where['column']] = $where['value'];
                }
            }

            return $this->insert($colval);
        }

        // Otherwise update it.
        return $this->limit(1)->update($colval);
    }

    /**
     * Initiate an upsert statement that inserts or updates on duplicate key.
     *
     * @param array $colval
     * @return int
     * @throws ApplicationManagerException
     * @throws Exception
     */
    public function upsert(array $colval): int
    {
        if(empty($colval)) {
            return 0;
        }

        //Duplicate the bindings.
        $bindings = array_merge(array_values($colval), array_values($colval));

        $this->columns = array_keys($colval);
        $this->bind($bindings, 'values');

        return $this->dispatcher->affect(
            $this->expression->toUpsert($this),
            $this->flattenBindings($this->bindings['values'])
        );
    }

    /**
     * Increment a column by an amount.
     *
     * @param string $column
     * @param int $by
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function increment(string $column, int $by): int
    {
        $value = new Expression("{$this->expression->quote($column)} + $by");

        return $this->update([$column => $value]);
    }

    /**
     * Increment a column by an amount.
     *
     * @param string $column
     * @param int $by
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function decrement(string $column, int $by): int
    {
        $value = new Expression("{$this->expression->quote($column)} - $by");

        return $this->update([$column => $value]);
    }

    /**
     * Initiate a delete statement (by specifying an id)
     *
     * @param int|null $id
     * @return int
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     * @throws Exception
     */
    public function delete(int $id = null): int
    {
        if (!is_null($id)) {
            $this->where(self::IDCOLUMN, '=', $id);
        }

        return $this->dispatcher->delete(
            $this->expression->toDelete($this),
            $this->flattenBindings()
        );
    }

    /**
     * Initiate a select statement selecting only the first result.
     *
     * @param string ...$columns
     * @return mixed
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function first(string ...$columns): mixed
    {
        $this->columns = empty($columns)
            ? ['*']
            : $columns;

        return $this->dispatcher->first(
            $this->expression->toSelect($this),
            $this->flattenBindings()
        );
    }

    /**
     * Get the result of the select query.
     *
     * @param string ...$columns
     * @return mixed
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function get(string ...$columns): mixed
    {
        // Set the dispatcher to return an associated array.
        $this->dispatcher->getConnection()
            ->setFetchMode(PDO::FETCH_ASSOC);

        empty($columns)
            ?: $this->columns = $columns;

        return $this->dispatcher->select(
            $this->expression->toSelect($this),
            $this->flattenBindings()
        );
    }

    /**
     * Get the result of the select query as an array container.
     *
     * @param string ...$columns
     * @return RecursiveArrayBag
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function collect(string ...$columns): RecursiveArrayBag
    {
        empty($columns)
            ?: $this->columns = $columns;

        $results = $this->dispatcher->select(
            $this->expression->toSelect($this),
            $this->flattenBindings()
        );

        foreach ((array)$results as $result) {
            $container[] = (array)$result;
        }

        return new RecursiveArrayBag($container ?? []);
    }

    /**
     * Query the results and return the generator
     *
     * @return Generator
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function cursor(): Generator
    {
        return yield from $this->dispatcher->while(
            $this->expression->toSelect($this),
            $this->flattenBindings()
        );
    }

    /**
     * Query the existence of the result.
     *
     * @return bool
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function exists(): bool
    {
        $results = $this->dispatcher->first(
            $this->expression->toExists($this),
            $this->flattenBindings()
        );

        return is_array($results)
            ? (bool)$results['exists']
            : (bool)$results->exists;
    }

    /**
     * Query the ! existence of the result.
     *
     * @return bool
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function notExists(): bool
    {
        return ! $this->exists();
    }

    /**
     * Set the query dispatcher.
     *
     * @param QueryDispatcher $dispatcher
     * @return $this
     */
    public function setQueryDispatcher(QueryDispatcher $dispatcher): QueryBuilder
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Get the query dispatcher.
     *
     * @return QueryDispatcher
     */
    public function getDispatcher(): QueryDispatcher
    {
        return $this->dispatcher;
    }

    /**
     * Set the expression language factory.
     *
     * @param Language $factory
     * @return $this
     */
    public function setExpressionLanguage(Language $factory): QueryBuilder
    {
        $this->expression = $factory;

        return $this;
    }

    /**
     *  Reset the instance and return it.
     *
     * @param string|Expression $table
     * @return QueryBuilder
     * @throws Exception
     */
    public function new(string|Expression $table): QueryBuilder
    {
        return (new self)
            ->table($table instanceof Expression ? $table->getExpression() : $table)
            ->setQueryDispatcher($this->dispatcher)
            ->setExpressionLanguage($this->expression);
    }

    /**
     * Clone this query builder without properties.
     *
     * @param string ...$properties
     * @return QueryBuilder
     */
    public function cloneWithout(string ...$properties): QueryBuilder
    {
        $clone = (clone $this);

        foreach ($properties as $property) {

            if(isset($clone->{$property}) && is_array($clone->{$property})) {
                $clone->{$property} = [];
            } else {
                unset($clone->{$property});
            }
        }

        return $clone;
    }

}
