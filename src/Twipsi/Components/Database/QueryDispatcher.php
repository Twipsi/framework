<?php

namespace Twipsi\Components\Database;

use PDO;
use Closure;
use Exception;
use Generator;
use PDOStatement;
use DateTimeInterface;
use Twipsi\Components\Database\Events\QueryCompletedEvent;
use Twipsi\Components\Database\Events\StatementPreparedEvent;
use Twipsi\Components\Database\Exceptions\QueryException;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\Events\EventHandler;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Chronos;
use Twipsi\Support\Traits\Timer;

final class QueryDispatcher
{
    use LogsQueries, MocksQueries, HandlesTransactions, Timer;

    /**
     * The database connection.
     *
     * @var IDatabaseConnection
     */
    protected IDatabaseConnection $connection;

    /**
     * The last insert ID.
     *
     * @var int|string
     */
    protected int|string $lastInsertID;

    /**
     * Modification status of queries.
     *
     * @var bool
     */
    protected bool $statusModified = false;

    /**
     * The total query duration.
     *
     * @var float
     */
    protected float $totalDuration = 0.0;

    /**
     * The event dispatcher.
     *
     * @var EventHandler
     */
    protected EventHandler $event;

    /**
     * Construct Query dispatcher.
     *
     * @param IDatabaseConnection $connection
     */
    public function __construct(IDatabaseConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Initialize the built-up query callback.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     * @throws ApplicationManagerException
     */
    protected function init(string $query, array $bindings, Closure $callback): mixed
    {
        // Start micro time to log the duration.
        $this->startTimer();

        try {
            $result = $this->runQuery($query, $bindings, $callback);

        } catch (QueryException $e) {
            $result = $this->tryAgain($query, $bindings, $callback, $e);
        }

        // Complete and log the query.
        $this->completeQuery($query, $bindings, $this->stopTimer());

        return $result;
    }

    /**
     * Register and log the query.
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     * @throws ApplicationManagerException
     */
    protected function completeQuery(string $query, array $bindings, float $time): void
    {
        $this->totalDuration += $time;

        !isset($this->event)
            ?: $this->event->dispatch(QueryCompletedEvent::class, $query, $time);

        if($this->shouldLogQueries) {
            $this->logQuery($query, $bindings, $time);
        }
    }

    /**
     * Run the query callback.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     */
    protected function runQuery(string $query, array $bindings, Closure $callback): mixed
    {
        try {
            return $callback($query);

        } catch (Exception $e) {
            throw new QueryException($this->formatQuery($query, $bindings), $e);
        }
    }

    /**
     * Attempt to connect again through the driver.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @param QueryException $e
     * @return mixed
     */
    protected function tryAgain(string $query, array $bindings, Closure $callback, QueryException $e): mixed
    {
        if ($this->isNestedTransaction()) {
            throw $e;
        }

        if (DatabaseDriver::isConnectionError($e->getPrevious())) {

            $this->connection->reconnect();
            return $this->runQuery($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Prepare query for transmission using defined "fetch mode".
     *
     * @param string $query
     * @return PDOStatement
     * @throws ApplicationManagerException
     */
    public function prepare(string $query): PDOStatement
    {
        $pdo = $this->connection->getPDO();

        $statement = $pdo->prepare($query);
        $statement->setFetchMode(...$this->connection->getfetchMode());

        !isset($this->event)
            ?: $this->event->dispatch(StatementPreparedEvent::class, $statement);

        return $statement;
    }

    /**
     * Parse and bind values to the specified query.
     *
     * @param PDOStatement $statement
     * @param array $bindings
     * @return void
     */
    public function bind(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $identifier => $value) {

            $type = match ($value) {
                is_int($value) || is_bool($value) => PDO::PARAM_INT,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $identifier = is_string($identifier) ? $identifier : $identifier+1;
            $statement->bindValue($identifier, $value, $type);
        }
    }

    /**
     * Parse and process bindings before binding to query.
     *
     * @param array $bindings
     * @return array
     */
    public function processBindings(array $bindings): array
    {
        foreach ($bindings as $identifier => $value) {

            $bindings[$identifier] = match (true) {
                $value instanceof Chronos => $value->getDateTime(),
                $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
                is_bool($value) => (int)$value,
                default => $value,
            };
        }

        return $bindings;
    }

    /**
     * Execute a raw query directly and return the result.
     *
     * @param string $query
     * @return mixed
     * @throws QueryException|ApplicationManagerException
     */
    public function raw(string $query): mixed
    {
        return $this->init($query, [], function ($query) {

            if($this->mocking) { return true; }

            $pdo = $this->connection->getPDO();
            $result = $pdo->exec($query);
            $this->setStatusModified($result !== false);

            return $result;
        });
    }

    /**
     * Transmit the query returning the executed status.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     * @throws QueryException|ApplicationManagerException
     */
    public function introduce(string $query, array $bindings = []): bool
    {
        return $this->init($query, $bindings, function ($query) use ($bindings) {

            if($this->mocking) { return true; }

            $statement = $this->prepare($query);
            $this->bind($statement, $this->processBindings($bindings));
            $this->setStatusModified();

            return $statement->execute();
        });
    }

    /**
     * Initialize a Select operation and return the result.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $first
     * @return mixed
     * @throws QueryException|ApplicationManagerException
     */
    public function select(string $query, array $bindings = [], bool $first = false): mixed
    {
        return $this->init($query, $bindings, function ($query) use ($bindings, $first) {

            if($this->mocking) { return []; }

            $statement = $this->prepare($query);
            $this->bind($statement, $this->processBindings($bindings));
            $statement->execute();

            return $first ? $statement->fetch() : $statement->fetchAll();
        });
    }

    /**
     * Affect existing data and return the affected data count.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     * @throws QueryException|ApplicationManagerException
     */
    public function affect(string $query, array $bindings = []): int
    {
        return $this->init($query, $bindings, function ($query) use ($bindings) {

            if($this->mocking) { return 0; }

            $statement = $this->prepare($query);
            $this->bind($statement, $this->processBindings($bindings));
            $statement->execute();

            $affected = $statement->rowCount();
            $this->setStatusModified($affected > 0);

            return $affected;
        });
    }

    /**
     * Query the results and return the generator.
     *
     * @param string $query
     * @param array $bindings
     * @return Generator
     * @throws QueryException|ApplicationManagerException
     */
    public function while(string $query, array $bindings = []): Generator
    {
        $statement = $this->init($query,  $bindings, function ($query) use ($bindings) {

            if($this->mocking) { return []; }

            $statement = $this->prepare($query);
            $this->bind($statement, $this->processBindings($bindings));
            $statement->execute();

            return $statement;
        });

        // Return the generator.
        while($row = $statement->fetch()) {
            yield $row;
        }
    }

    /**
     * Initialize a Select operation and return the first result.
     *
     * @param string $query
     * @param array $bindings
     * @return mixed
     * @throws ApplicationManagerException
     */
    public function first(string $query, array $bindings = []): mixed
    {
        return $this->select($query, $bindings, true);
    }

    /**
     * Initialize an Insert operation and return the state.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     * @throws QueryException|ApplicationManagerException
     */
    public function insert(string $query, array $bindings = []): bool
    {
        $statement = $this->introduce($query, $bindings);
        $this->lastInsertID = $this->connection->getPDO()->lastInsertId();

        return $statement;
    }

    /**
     * Initialize an Update operation and return the affected rows count.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     * @throws QueryException|ApplicationManagerException
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->affect($query, $bindings);
    }

    /**
     * Initialize a Delete operation and return the affected rows count.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     * @throws ApplicationManagerException
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->affect($query, $bindings);
    }

    /**
     * Set the connection status to modify.
     *
     * @param bool $state
     * @return void
     */
    public function setStatusModified(bool $state = true): void
    {
        if (!$this->statusModified) {
            $this->statusModified = $state;
        }
    }

    /**
     * Check if the connection status has been modified.
     *
     * @return bool
     */
    public function hasBeenModified(): bool
    {
        return $this->statusModified;
    }

    /**
     * Get the total query duration.
     *
     * @return float
     */
    public function queryDuration(): float
    {
        return $this->totalDuration;
    }

    /**
     * Reset query duration.
     *
     * @return void
     */
    public function resetQueryDuration(): void
    {
        $this->totalDuration = 0.0;
    }

    /**
     * Disconnect PDO.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection->disconnect();
    }

    /**
     * Get the last inserted id.
     *
     * @return int|string
     */
    public function lastInserted(): int|string
    {
        return $this->lastInsertID;
    }

    /**
     * Set the event handler.
     *
     * @param EventHandler $dispatcher
     * @return void
     */
    public function setEventDispatcher(EventHandler $dispatcher): void
    {
        $this->event = $dispatcher;
    }

    /**
     * Get the database connection.
     * @return IDatabaseConnection
     */
    public function getConnection(): IDatabaseConnection
    {
        return $this->connection;
    }
}