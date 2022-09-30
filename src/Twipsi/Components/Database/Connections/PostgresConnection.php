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

use Twipsi\Components\Database\Connections\Connection;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;

class PostgresConnection extends Connection implements IDatabaseConnection
{
}
