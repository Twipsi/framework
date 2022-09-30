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

use PDO;
use PDOException;
use Throwable;
use InvalidArgumentException;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Support\Str;

abstract class DatabaseDriver
{
    /**
     * The default PDO connection options.
     */
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * The PDO error messages.
     */
    protected static array $errors = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
    ];

    /**
     * Create the PDO connection.
     *
     * @param string $dsn
     * @param ConfigRegistry $config
     * @param array $options
     * @return PDO
     */
    public function createConnection(string $dsn, ConfigRegistry $config, array $options = []): PDO
    {
        // Merge all the options.
        $options = $this->buildOptions($options);

        try {
            return $this->connectWithPdo($dsn, $config->get('username', ''), $config->get('password', ''), $options);

            // If the connection failed attempt to connect again,
        } catch (PDOException $e) {
            return $this->tryAgain($e, $dsn, $config->get('username', ''), $config->get('password', ''), $options);
        }
    }

    /**
     * Build options based on default and custom.
     */
    public function buildOptions(array $options): array
    {
        return array_diff_key($this->options, $options) + $options;
    }

    /**
     * Connect with PDO.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return PDO
     */
    protected function connectWithPdo(string $dsn, string $username, string $password, array $options): PDO
    {
        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Attempt to connect again after fail.
     *
     * @param PDOException $e
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return PDO
     * @throws PDOException
     */
    protected function tryAgain(PDOException $e, string $dsn, string $username, string $password, array $options): PDO
    {
        if (static::isConnectionError($e)) {
            return $this->connectWithPdo($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Check if error is due to lost connection.
     *
     * @param Throwable $e
     * @return bool
     */
    public static function isConnectionError(Throwable $e): bool
    {
        return Str::hay($e->getMessage())->resembles(...static::$errors);
    }

    /**
     * Get default options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set default options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): DatabaseDriver
    {
        $this->options = $options;

        return $this;
    }
}
