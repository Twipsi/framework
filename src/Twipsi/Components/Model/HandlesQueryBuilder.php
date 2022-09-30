<?php

namespace Twipsi\Components\Model;

use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Components\Database\DatabaseManager;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;

trait HandlesQueryBuilder
{
    /**
     * The driver name of the db connection.
     *
     * @var string
     */
    protected string $driver;

    /**
     * The database manager.
     *
     * @var DatabaseManager
     */
    protected static DatabaseManager $DBmanager;
    
    /**
     * Initiate model query.
     *
     * @return ModelQueryFactory
     */
    public static function query(): ModelQueryFactory
    {
        return (new static)->newQuery();
    }

    /**
     * Create a new Query factory.
     *
     * @return ModelQueryFactory
     */
    public function newQuery(): ModelQueryFactory
    {
        return $this->createModelQuery()
            ->filters($this->filters)
            ->with(...$this->with);
    }

    /**
     * Create new model query without the with.
     *
     * @return ModelQueryFactory
     */
    public function newNonRelatedQuery(): ModelQueryFactory
    {
        return $this->createModelQuery()
            ->filters($this->filters);
    }

    /**
     * Create new model query factory with (with) data.
     *
     * @return ModelQueryFactory
     */
    public function newNonFilteredQuery(): ModelQueryFactory
    {
        return $this->createModelQuery()
            ->with(...$this->with);
    }

    /**
     * Create the model query factory including the db connection.
     *
     * @return ModelQueryFactory
     */
    public function createModelQuery(): ModelQueryFactory
    {
        return $this->createQueryFactory($this->getConnection())
            ->model($this);
    }

    /**
     * Create a new model query factory.
     *
     * @param IDatabaseConnection $connection
     * @return ModelQueryFactory
     */
    protected function createQueryFactory(IDatabaseConnection $connection): ModelQueryFactory
    {
        return new ModelQueryFactory($connection);
    }

    /**
     * Get the database connection query builder.
     *
     * @return QueryBuilder
     */
    protected function newDatabaseQueryBuilder(): QueryBuilder
    {
        return $this->getConnection()->query();
    }

    /**
     * Get the database connection.
     *
     * @return IDatabaseConnection
     */
    public function getConnection(): IDatabaseConnection
    {
        return static::resolveConnection($this->getDriver() ?? 'mysqli');
    }

    /**
     * Get the DB driver to use.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver ?? 'mysqli';
    }

    /**
     * Set the DB driver to use.
     *
     * @param string $driver
     * @return $this
     */
    public function setDriver(string $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Resolve the connector and create the connection.
     *
     * @param string $driver
     * @return IDatabaseConnection
     */
    public static function resolveConnection(string $driver): IDatabaseConnection
    {
        return static::getDBManager()->create($driver);
    }

    /**
     * Get the database manager.
     *
     * @return DatabaseManager
     */
    public static function getDBManager(): DatabaseManager
    {
        return static::$DBmanager;
    }

    /**
     * Set the database manager.
     *
     * @param DatabaseManager $DBmanager
     * @return void
     */
    public static function setDBManager(DatabaseManager $DBmanager): void
    {
        static::$DBmanager = $DBmanager;
    }
}