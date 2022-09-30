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

interface IDatabaseDriver
{
    /**
     * Attempt to reconnect.
     *
     * @return PDO
     * @throws InvalidArgumentException|PDOException
     */
    public function reconnect(): PDO;

    /**
     * Initialize database connection.
     *
     * @param array $options
     * @return PDO
     * @throws InvalidArgumentException|PDOException
     */
    public function connect(array $options = []): PDO;
}
