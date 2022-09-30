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

namespace Twipsi\Components\Authorization\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    /**
     * The status code.
     * 
     * @var int
     */
    protected int $status;

    /**
     * Create a new exception instance.
     * 
     * @param string $action
     * @param string $where
     * @param int|null $status
     */
    public function __construct(string $action, string $where, int $status = null)
    {
        if(!is_null($status)) {
            $this->status = $status;
        }

        parent::__construct(sprintf("User is not permited to [%s] in [%s]", $action, $where));
    }

    /**
     * get the status code.
     * 
     * @return int|null
     */
    public function getStatus(): ?int 
    {
        return isset($this->status) ? $this->status : null;
    }
}
