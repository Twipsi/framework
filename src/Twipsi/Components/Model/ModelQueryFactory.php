<?php

namespace Twipsi\Components\Model;

use Closure;
use Exception;
use PDO;
use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\Database\Language\Expression;
use Twipsi\Components\Model\Exceptions\ModelPropertyException;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Components\Model\Exceptions\ModelNotFoundException;

class ModelQueryFactory
{
    /**
     * The database connection
     *
     * @var IDatabaseConnection
     */
    protected IDatabaseConnection $connection;

    /**
     * The database query builder.
     *
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    /**
     * The model in hand.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * List of filters to use globally.
     *
     * @var array
     */
    protected array $filters;

    /**
     * Construct Model query factory.
     *
     * @param IDatabaseConnection $connection
     */
    public function __construct(IDatabaseConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the database query builder.
     *
     * @return QueryBuilder|null
     */
    public function queryBuilder(): ?QueryBuilder
    {
        return $this->query ?? null;
    }

    /**
     * Set the model in hand.
     *
     * @param Model $model
     * @return $this
     * @throws Exception
     */
    public function model(Model $model): ModelQueryFactory
    {
        $this->model = $model;

        $this->openTable($model->table());

        return $this;
    }

    /**
     * Set global query filters.
     *
     * @param array $filters
     * @return $this
     */
    public function filters(array $filters): ModelQueryFactory
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Set a global query filter.
     *
     * @param array $filter
     * @return $this
     */
    public function withFilters(array $filter): ModelQueryFactory
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Open connection to the table.
     *
     * @param string $table
     * @return void
     * @throws Exception
     */
    protected function openTable(string $table): void
    {
        $this->query = $this->connection
            ->open($table);
    }

    /**
     * Create a model collection from an array of results.
     *
     * @param array $models
     * @return ModelCollection
     * @throws ModelPropertyException
     */
    public function modelize(array $models): ModelCollection
    {
        $instance = $this->newModel();

        $models = array_map(function($model) use($instance) {
            return $instance->newLiveInstance((array) $model);
        }, $models);

        return new ModelCollection($models);
    }

    /**
     * Resolve the primary key column and value,
     * then set the where clause on the query builder.
     *
     * @param Model|array|int|string $id
     * @return $this
     * @throws NotSupportedException
     * @throws Exception
     */
    public function wherePrimary(Model|array|int|string $id): static
    {
        if($id instanceof Model) {
            $id = $id->key();
        }

        is_array($id)
            ? $this->query->whereIn($this->model->tablePrimary(), $id)
            : $this->query->where($this->model->tablePrimary(), '=', $id);

        return $this;
    }

    /**
     * Resolve the primary key column and value,
     * then set the !where clause on the query builder.
     *
     * @param Model|array|int|string $id
     * @return $this
     * @throws NotSupportedException
     * @throws Exception
     */
    public function wherePrimaryNot(Model|array|int|string $id): static
    {
        if($id instanceof Model) {
            $id = $id->key();
        }

        is_array($id)
            ? $this->query->whereNotIn($this->model->tablePrimary(), $id)
            : $this->query->where($this->model->tablePrimary(), '!=', $id);

        return $this;
    }

    /**
     * Add a where clause.
     *
     * @param string|Closure|Expression $column
     * @param string|null $operator
     * @param mixed|null $value
     * @param string $condition
     * @return ModelQueryFactory
     * @throws Exception
     */
    public function where(string|Closure|Expression $column, string $operator = null, mixed $value = null, string $condition = 'and'): ModelQueryFactory
    {
        if ($column instanceof Closure && is_null($operator)) {
            $column($query = $this->model->newNonRelatedQuery());

            $this->query->nestWhere($this->query, $condition);
        } else {
            $this->query->where(...func_get_args());
        }

        return $this;
    }

    /**
     * Add a where clause with condition (OR) condition.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return ModelQueryFactory
     * @throws Exception
     */
    public function orWhere(string|Closure $column, string $operator = null, mixed $value = null): ModelQueryFactory
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
     * @return ModelQueryFactory
     * @throws Exception
     */
    public function whereNot(string|Closure $column, string $operator = null, mixed $value = null, string $condition = 'and'): ModelQueryFactory
    {
        return $this->where($column, $operator, $value, $condition.' not');
    }

    /**
     * Create where clause with NOT condition.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return ModelQueryFactory
     * @throws Exception
     */
    public function orWhereNot(string|Closure $column, string $operator = null, mixed $value = null): ModelQueryFactory
    {
        return $this->where($column, $operator, $value, 'or not');
    }

    /**
     * Automatically order for the newest model.
     *
     * @param string|Expression|QueryBuilder|null $column
     * @return ModelQueryFactory
     * @throws NotSupportedException
     */
    public function newest(string|Expression|QueryBuilder $column = null): ModelQueryFactory
    {
        $column = $column ?? $this->model->createdAt();

        $this->query->newest($column);

        return $this;
    }

    /**
     * Automatically order for the oldest model.
     *
     * @param string|Expression|QueryBuilder|null $column
     * @return ModelQueryFactory
     * @throws NotSupportedException
     */
    public function oldest(string|Expression|QueryBuilder $column = null): ModelQueryFactory
    {
        $column = $column ?? $this->model->createdAt();

        $this->query->oldest($column);

        return $this;
    }

    /**
     * Find a model based on the primary key.
     *
     * @param int|string $id
     * @param string ...$columns
     * @return Model|null
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function find(int|string $id, string ...$columns): ?Model
    {
        return $this->wherePrimary($id)
            ->first(...(empty($columns) ? ['*'] : $columns));
    }

    /**
     * Find many models based on the primary key.
     *
     * @param array $ids
     * @param string ...$columns
     * @return ModelCollection
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function findMany(array $ids, string ...$columns): ModelCollection
    {
        return $this->wherePrimary($ids)
            ->get(...(empty($columns) ? ['*'] : $columns));
    }

    /**
     * Find models based on the primary key or throw an exception.
     *
     * @param int|string|array $ids
     * @param string ...$columns
     * @return ModelCollection|Model
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public function findOrFail(int|string|array $ids, string ...$columns): ModelCollection|Model
    {
        if (is_array($ids)) {
            if(count($result = $this->findMany($ids, ...$columns)) !== count($ids)) {
                $diff = array_diff($ids, $result->modelIDs());
                throw new ModelNotFoundException($this->model, $diff);
            }

            return $result;
        }

        if(is_null($result = $this->find($ids, ...$columns))) {
            throw new ModelNotFoundException($this->model, [$ids]);
        }

        return $result;
    }

    /**
     * Find models based on the primary key or return a new model.
     *
     * @param int|string $id
     * @param string ...$columns
     * @return Model
     * @throws ApplicationManagerException
     * @throws NotSupportedException|ModelPropertyException
     */
    public function findOrNew(int|string $id, string ...$columns): Model
    {
        if(! is_null($result = $this->find($id, ...$columns))) {
            return $result;
        }

        return $this->newModel();
    }

    /**
     * Get the first model in the result set.
     *
     * @param string ...$columns
     * @return mixed
     * @throws ApplicationManagerException
     * @throws NotSupportedException|ModelPropertyException
     */
    public function first(string ...$columns): ?Model
    {
        $result = $this->query->first(...$columns);

        return $result 
            ? $this->modelize([$result])->all()[0]
            : null;
    }

    /**
     * Get the results in a model collection.
     *
     * @param string ...$columns
     * @return ModelCollection
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     * @throws Exception
     */
    public function get(string ...$columns): ModelCollection
    {
        $query = $this->applyFilters();

        return $query->getCollection(...$columns);
    }

    /**
     * Get the result as an array of built models.
     *
     * @param string ...$columns
     * @return array
     * @throws ApplicationManagerException
     * @throws ModelPropertyException
     * @throws NotSupportedException
     */
    public function getModels(string ...$columns): array
    {
        return $this->modelize(
            $this->query->get(...$columns)
        )->all();
    }

    /**
     * Get the result as a collection of built models.
     *
     * @param string ...$columns
     * @return ModelCollection
     * @throws ApplicationManagerException
     * @throws ModelPropertyException
     * @throws NotSupportedException
     */
    public function getCollection(string ...$columns): ModelCollection
    {
        return $this->modelize(
            $this->query->collect(...$columns)->all()
        );
    }

    /**
     * Apply all the custom filters before query.
     *
     * @return $this
     * @throws Exception
     */
    protected function applyFilters(): ModelQueryFactory
    {
        foreach ($this->filters as $filter) {
            $this->query->where($filter[0], $filter[1], $filter[2]);
        }

        return $this;
    }



    public function with(string ...$relations): ModelQueryFactory
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Create a new model instance.
     *
     * @return Model
     * @throws ModelPropertyException
     */
    public function newModel(): Model
    {
        return $this->model->newModelInstance()
            ->setDriver($this->model->getDriver());
    }

    /**
     * Forward calls to the query builder.
     *
     * @param string $method
     * @param array|null $parameters
     * @method where(string $string)
     * @return mixed
     */
    public function __call(string $method, array $parameters = null)
    {
        if(! property_exists($this, $method) && !method_exists($this, $method)) {
            return $this->query->{$method}(...$parameters);
        }

        return $this->{$method}($parameters);
    }
}