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

namespace Twipsi\Components\Database;

use Closure;
use InvalidArgumentException;
use Twipsi\Components\Database\Connections\MySqlConnection;
use Twipsi\Components\Database\Connections\PostgresConnection;
use Twipsi\Components\Database\Connections\SQLiteConnection;
use Twipsi\Components\Database\Drivers\MySqlDriver;
use Twipsi\Components\Database\Drivers\PostgresDriver;
use Twipsi\Components\Database\Drivers\SQLiteDriver;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\Database\Interfaces\IDatabaseDriver;
use Twipsi\Foundation\ComponentManager;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Exceptions\NotSupportedException;

final class DatabaseManager extends ComponentManager
{
    /**
     * Shortcut driver resolver.
     *
     * @param string|null $driver
     * @return IDatabaseConnection
     */
    public function create(string $driver = null): IDatabaseConnection
    {
        return $this->driver($driver);
    }

    /**
     * Resolve driver and create PDO connection.
     *
     * @param string $driver
     * @return IDatabaseConnection
     * @throws NotSupportedException
     */
    protected function resolve(string $driver): IDatabaseConnection
    {
        if (!($config = $this->app->get('config')->get("database.connections." . $driver))) {
            throw new NotSupportedException(
                sprintf("No database configuration found for driver [%s]", $driver)
            );
        }

        // This will be passed as a closure, so it can be opened
        // only when we actually need it.
        return $this->createDriverConnection(
            $driver, $this->createPdoConnector($config));
    }

    /**
     * Get the default driver set.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app->get('config')->get("database.driver");
    }

    /**
     * Create PDO instance resolving closure for lazy loading.
     *
     * @param ConfigRegistry $config
     * @return Closure
     */
    protected function createPdoConnector(ConfigRegistry $config): Closure
    {
        return function() use ($config) {
            return $this->createDriverConnector($config);
        };
    }

    /**
     * Create a new database connector.
     *
     * @param ConfigRegistry $config
     * @return IDatabaseDriver
     */
    protected function createDriverConnector(ConfigRegistry $config): IDatabaseDriver
    {
        if (!$config->has('driver')) {
            throw new InvalidArgumentException('No driver has been specified for the database connection');
        }

        return match ($config->get('driver')) {
            'mysqli' => new MySqlDriver($config),
            'pgsql' => new PostgresDriver($config),
            'sqlite' => new SQLiteDriver($config),
            default => throw new InvalidArgumentException(
                sprintf('The requested driver [%s] is not supported', $config->get('driver'))
            ),
        };
    }

    /**
     * Create new database connection.
     *
     * @param string $driver
     * @param Closure $connector
     * @return IDatabaseConnection
     */
    protected function createDriverConnection(string $driver, Closure $connector): IDatabaseConnection
    {
        return match ($driver) {
            'mysqli' => new MySqlConnection($connector),
            'pgsql' => new PostgresConnection($connector),
            'sqlite' => new SQLiteConnection($connector),
            default => throw new InvalidArgumentException(
                sprintf('The requested driver [%s] is not supported', $driver)
            ),
        };
    }
}
