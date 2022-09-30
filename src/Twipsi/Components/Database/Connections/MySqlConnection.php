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

use PDO;
use Twipsi\Components\Database\Language\Language;
use Twipsi\Components\Database\Language\MysqlLanguage;
use Twipsi\Support\Str;

final class MySqlConnection extends Connection
{
    /**
     * Check if we are using MARIA DB.
     *
     * @return bool
     */
    public function isMaria(): bool
    {
        $version = $this->getPDO()->getAttribute(PDO::ATTR_SERVER_VERSION);

        return Str::hay($version)->resembles('MariaDB');
    }

    /**
     * Build mysql Query builder.
     *
     * @return Language
     */
    public function getExpressionLanguage(): Language
    {
        return $this->prependPrefixTo(new MysqlLanguage());
    }
}
