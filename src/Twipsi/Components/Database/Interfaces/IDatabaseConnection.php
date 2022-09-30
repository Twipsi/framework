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

namespace Twipsi\Components\Database\Interfaces;

use InvalidArgumentException;
use PDO;
use PDOException;
use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Components\Database\Connections\Connection;
use Twipsi\Components\Database\QueryDispatcher;

interface IDatabaseConnection
{
    /**
     * Get the PDO instance.
     *
     * @return PDO
     * @throws InvalidArgumentException|PDOException
     */
    public function getPDO(): PDO;

    /**
     * Set a PDO instance to the connection.
     *
     * @param PDO|null $pdo
     * @return $this
     */
    public function setPDO(?PDO $pdo): static;

    /**
     * Disconnect connection.
     *
     * @return $this
     */
    public function disconnect(): static;

    /**
     * Set the PDO fetch mode.
     *
     * @param int $mode
     * @param string|object|null $class
     * @return Connection
     */
    public function setFetchMode(int $mode, string|object $class = null): Connection;

    /**
     * Get the PDO fetch mode.
     *
     * @return array
     */
    public function getFetchMode(): array;

    /**
     * Attempt to reconnect.
     *
     * @return $this
     * @throws InvalidArgumentException|PDOException
     */
    public function reconnect(): static;

    /**
     * Open a query to a specified table (short for query->table())
     *
     * @param string|QueryBuilder $table
     * @param string|null $alias
     * @return QueryBuilder
     */
    public function open(string|QueryBuilder $table, string $alias = null): QueryBuilder;

    /**
     * Return a new query builder.
     *
     * @return QueryBuilder
     */
    public function query(): QueryBuilder;

    /**
     * Get the query dispatcher.
     *
     * @return QueryDispatcher
     */
    public function getDispatcher(): QueryDispatcher;
}
