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
use Twipsi\Components\Database\DatabaseDriver;
use Twipsi\Components\Database\Interfaces\IDatabaseDriver;
use Twipsi\Foundation\ConfigRegistry;

final class PostgresDriver extends DatabaseDriver implements IDatabaseDriver
{
    /**
     * Sql default variable options to set.
     */
    protected const SQL_MODES = [
    ];

    /**
     * Construct Database driver.
     */
    public function __construct(protected ConfigRegistry $config)
    {
    }


    /**
     * Initialize database connection.
     */
    public function connect(array $options = []): PDO
    {
        return $this->createConnection($this->config, $options);
    }

    /**
     * Set connection encoding.
     */
    public function setEncode(PDO $connection, string $charset, string $collation = ''): void
    {
    }

    /**
     * Set connection timezone.
     */
    public function setTimezone(PDO $connection, string $timezone): void
    {
    }

    /**
     * Set connection modes.
     */
    public function setModes(PDO $connection, array $modes, bool $strict = true): void
    {
    }

}
