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

namespace Twipsi\Components\Database\Exceptions;

use Exception;
use PDOException;

class QueryException extends PDOException
{
    /**
     * The query used.
     *
     * @var string
     */
    protected string $query;

    /**
     * Create a new exception instance.
     *
     * @param string $query
     * @param Exception $e
     */
    public function __construct(string $query, Exception $e)
    {
        parent::__construct('', 0, $e);

        $this->query = $query;
        $this->code = $e->getCode();
        $this->message = $e->getMessage().' SQL: ('.$query.')';

        if ($e instanceof PDOException) {
            $this->errorInfo = $e->errorInfo;
        }
    }

    /**
     * Return the query.
     *
     * @return string
     */
    public function query(): string
    {
        return $this->query;
    }
}
