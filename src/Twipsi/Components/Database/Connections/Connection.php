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

namespace Twipsi\Components\Database\Connections;

use Closure;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\Database\Interfaces\IDatabaseDriver;
use Twipsi\Components\Database\Language\Language;
use Twipsi\Components\Database\QueryDispatcher;
use Twipsi\Components\Events\EventHandler;

abstract class Connection implements IDatabaseConnection
{
    /**
     * PDO fetch mode.
     *
     * @var array
     */
    protected array $fetchMode = [PDO::FETCH_OBJ];

    /**
     * The PDO instance to use.
     *
     * @var PDO|null
     */
    protected PDO|null $pdo;

    /**
     * The driver loader.
     *
     * @var Closure|IDatabaseDriver
     */
    protected Closure|IDatabaseDriver $driver;

    /**
     * Query dispatcher.
     *
     * @var QueryDispatcher
     */
    protected QueryDispatcher $dispatcher;

    /**
     * The table prefix.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * The driver specific expression language.
     *
     * @var Language
     */
    protected Language $expressionLanguage;

    /**
     * Construct Database connection.
     *
     * @param Closure $driver
     * @param string $prefix
     */
    public function __construct(Closure $driver, string $prefix = '')
    {
        $this->driver = $driver;
        $this->prefix = $prefix;

        $this->dispatcher = $this->buildQueryDispatcher();

        $this->expressionLanguage = $this->getExpressionLanguage();
    }

    /**
     * Boot driver specific expression language.
     *
     * @return Language
     */
    abstract protected function getExpressionLanguage(): Language;

    /**
     * Open a query to a specified table (short for query->table())
     *
     * @param string|QueryBuilder $table
     * @param string|null $alias
     * @return QueryBuilder
     * @throws Exception
     */
    public function open(string|QueryBuilder $table, string $alias = null): QueryBuilder
    {
        return $this->query()->table($table, $alias);
    }

    /**
     * Return a new query builder.
     *
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        return $this->createQueryBuilder();
    }

    /**
     * Return the drivers expression language.
     *
     * @return Language
     */
    public function language(): Language
    {
        return $this->expressionLanguage;
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO
     * @throws InvalidArgumentException|PDOException
     */
    public function getPDO(): PDO
    {
        if (!($this->pdo ?? null) instanceof PDO) {

            if ($this->driver instanceof Closure) {
                return $this->pdo = (call_user_func($this->driver))->connect();
            }

            return $this->pdo = $this->driver->connect();
        }

        return $this->pdo;
    }

    /**
     * Set a PDO instance to the connection.
     *
     * @param PDO|null $pdo
     * @return $this
     */
    public function setPDO(?PDO $pdo): static
    {
        $this->dispatcher->flushTransactions();

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Disconnect connection.
     *
     * @return $this
     */
    public function disconnect(): static
    {
        $this->setPDO(null);

        return $this;
    }

    /**
     * Attempt to reconnect.
     *
     * @return $this
     * @throws InvalidArgumentException|PDOException
     */
    public function reconnect(): static
    {
        if ($this->driver instanceof Closure) {
            $pdo = (call_user_func($this->driver))->reconnect();

        } else {
            $pdo = $this->driver->reconnect();
        }

        $this->setPDO($pdo);

        return $this;
    }

    /**
     * Get the Database connector driver.
     *
     * @return Closure|IDatabaseDriver
     */
    public function getDriver(): Closure|IDatabaseDriver
    {
        return $this->driver;
    }

    /**
     * Set the Database connector driver.
     *
     * @param Closure|IDatabaseDriver $driver
     * @return $this
     */
    public function setDriver(Closure|IDatabaseDriver $driver): Connection
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Set the fetch mode for PDO.
     *
     * @param int $mode
     * @param string|object|null $class
     * @return $this
     */
    public function setFetchMode(int $mode, string|object $class = null): Connection
    {
        $this->fetchMode = !is_null($class) ? [$mode, $class] : [$mode];
        return $this;
    }

    /**
     * Get the PDO fetch mode.
     *
     * @return array
     */
    public function getFetchMode(): array
    {
        return $this->fetchMode;
    }

    /**
     * Prepend the prefix to the expression language.
     *
     * @param Language $language
     * @return Language
     */
    protected function prependPrefixTo(Language $language): Language
    {
        return $language->setPrefix($this->prefix);
    }

    /**
     * Build the query dispatcher.
     *
     * @return QueryDispatcher
     */
    protected function buildQueryDispatcher(): QueryDispatcher
    {
        return (new QueryDispatcher($this));
    }

    /**
     * Attach the dispatcher and the expression factory to a new query builder.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder() : QueryBuilder
    {
        return (new QueryBuilder)
            ->setQueryDispatcher($this->dispatcher)
            ->setExpressionLanguage($this->expressionLanguage);
    }

    /**
     * Set the event dispatcher on the query dispatcher.
     *
     * @param EventHandler $event
     * @return Connection
     */
    public function setEventDispatcher(EventHandler $event): Connection
    {
        $this->dispatcher->setEventDispatcher($event);

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
     * Forward calls into the query dispatcher.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        if(!method_exists($this, $method)) {
            return $this->dispatcher->{$method}(...$parameters);
        }

        return $this->{$method}(...$parameters);
    }
}
