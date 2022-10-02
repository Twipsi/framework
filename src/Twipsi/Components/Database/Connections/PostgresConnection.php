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

use Twipsi\Components\Database\Language\Language;
use Twipsi\Components\Database\Language\PostgresLanguage;

final class PostgresConnection extends Connection
{
    /**
     * Build postgres Query builder.
     *
     * @return Language
     */
    public function getExpressionLanguage(): Language
    {
        return $this->prependPrefixTo(new PostgresLanguage());
    }
}
