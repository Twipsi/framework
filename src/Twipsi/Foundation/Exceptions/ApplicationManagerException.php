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

namespace Twipsi\Foundation\Exceptions;

use Exception;

class ApplicationManagerException extends Exception
{
    /**
     * Create application manager exception.
     *
     * @return void
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 500);
    }
}
