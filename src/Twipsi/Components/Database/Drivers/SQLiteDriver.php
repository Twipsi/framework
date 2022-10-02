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

namespace Twipsi\Components\Database\Drivers;

use PDO;
use PDOException;
use InvalidArgumentException;
use Twipsi\Components\Database\DatabaseDriver;
use Twipsi\Components\Database\Interfaces\IDatabaseDriver;
use Twipsi\Foundation\ConfigRegistry;

final class SQLiteDriver extends DatabaseDriver implements IDatabaseDriver
{
    /**
     * The configuration registry.
     *
     * @var ConfigRegistry
     */
    protected ConfigRegistry $config;

    /**
     * Construct Database driver.
     *
     * @param ConfigRegistry $config
     */
    public function __construct(ConfigRegistry $config)
    {
        $this->config = $config;
    }

    /**
     * Attempt to reconnect.
     *
     * @return PDO
     * @throws InvalidArgumentException|PDOException
     */
    public function reconnect(): PDO
    {
        return $this->connect();
    }

    /**
     * Initialize database connection.
     *
     * @param array $options
     * @return PDO
     * @throws InvalidArgumentException|PDOException
     */
    public function connect(array $options = []): PDO
    {
        $options = array_merge($options, $this->config->get('options', [])->all());

        if ($this->config['database'] === ':memory:') {
            return $this->createConnection('sqlite::memory:', $this->config, $options);
        }

        if(! $path = realpath($this->config['database'])) {
            throw new InvalidArgumentException('Database information path is invalid');
        }

        return $this->createConnection("sqlite:{$path}", $this->config, $options);
    }
}
