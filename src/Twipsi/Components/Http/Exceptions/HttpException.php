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

namespace Twipsi\Components\Http\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    /**
     * Status Code.
     * 
     * @var int
     */
    public int $status;

    /**
     * Headers container.
     * 
     * @var array
     */
    public array $headers;

    /**
     * Construct an Http Exception.
     * 
     * @param int $status
     * @param string $message
     * @param Throwable|null $e
     * @param array $headers
     * @param int $code
     */
    public function __construct(int $status, string $message = "", Throwable $e = null, array $headers = [], int $code = 0)
    {
        $this->status = $status;
        $this->headers = $headers;
  
        parent::__construct($message, $code, $e);
    }

    /**
     * Set custom headers.
     * 
     * @param array $headers
     * 
     * @return void
     */
    public function setHeaders(array $headers): void 
    {
        $this->headers = $headers;
    }
}
