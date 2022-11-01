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

namespace Twipsi\Support\Applicables;

use Twipsi\Components\Database\DatabaseManager;

trait Database
{
    protected DatabaseManager $db;

    /**
     * Set the cookies instance.
     */
    public function appendDatabase(DatabaseManager $connector): static
    {
        $this->db = $connector;

        return $this;
    }

    /**
     * Get the cookies instance.
     */
    public function getDatabase()
    {
        return $this->db;
    }

}
