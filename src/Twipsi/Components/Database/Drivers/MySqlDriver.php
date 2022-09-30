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

final class MySqlDriver extends DatabaseDriver implements IDatabaseDriver
{
    /**
     * The configuration registry.
     *
     * @var ConfigRegistry
     */
    protected ConfigRegistry $config;

    /**
     * Sql default variable options to set.
     */
    protected const SQL_MODES = [
        'ONLY_FULL_GROUP_BY',
        'STRICT_TRANS_TABLES',
        'NO_ZERO_IN_DATE',
        'NO_ZERO_DATE',
        'ERROR_FOR_DIVISION_BY_ZERO',
        'NO_AUTO_CREATE_USER',
        'NO_ENGINE_SUBSTITUTION',
    ];

    /**
     * Construct Database driver.
     *
     * @param ConfigRegistry $config
     */
    public function __construct(ConfigRegistry $config)
    {
        if (!$config->has('host') || !$config->has('username')
            || !$config->has('password') || !$config->has('database')) {

            throw new InvalidArgumentException('Database information array is incomplete');
        }

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
        $dsn = 'mysql:dbname=' . $this->config->get('database') . ';host=' . $this->config->get('host');

        $connection = $this->createConnection($dsn, $this->config,
            array_merge($options, $this->config->get('options')?->all())
        );

        if ($this->config->has('database')) {
            $connection->exec("use `{$this->config->get('database')}`");
        }

        if ($this->config->has('charset')) {
            $this->setEncode($connection, $this->config->get('charset'), $this->config->get('collation') ?? '');
        }

        if ($this->config->has('timezone')) {
            $this->setTimezone($connection, $this->config->get('timezone'));
        }

        if ($this->config->has('modes')) {
            $this->setModes($connection, $this->config->get('modes'), false);

        } else {
            $this->setModes($connection, self::SQL_MODES);
        }

        return $connection;
    }

    /**
     * Set the connection encoding.
     *
     * @param PDO $connection
     * @param string $charset
     * @param string $collation
     * @return void
     */
    protected function setEncode(PDO $connection, string $charset, string $collation = ''): void
    {
        $collation = !empty($collation) ? "collate '{$collation}'" : '';
        $connection->prepare("set names '{$charset}' $collation")->execute();
    }

    /**
     * Set connection timezone.
     *
     * @param PDO $connection
     * @param string $timezone
     * @return void
     */
    protected function setTimezone(PDO $connection, string $timezone): void
    {
        $connection->prepare("set time_zone='{$timezone}'")->execute();
    }

    /**
     * Set connection modes.
     *
     * @param PDO $connection
     * @param array $modes
     * @param bool $strict
     * @return void
     */
    protected function setModes(PDO $connection, array $modes, bool $strict = true): void
    {
        if (!empty($modes)) {
            $modes = implode(',', $modes);
            $connection->prepare("set session sql_mode='{$modes}'")->execute();
        }

        elseif ($strict) {

            $connection->prepare("set session sql_mode='
                ONLY_FULL_GROUP_BY,
                STRICT_TRANS_TABLES,
                NO_ZERO_IN_DATE,
                NO_ZERO_DATE,
                ERROR_FOR_DIVISION_BY_ZERO,
                NO_AUTO_CREATE_USER,
                NO_ENGINE_SUBSTITUTION'"
            )->execute();
        }

        else {
            $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
        }
    }
}
