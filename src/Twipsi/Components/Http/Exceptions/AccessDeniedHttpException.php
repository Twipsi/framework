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

use Throwable;

class AccessDeniedHttpException extends HttpException
{
    /**
     * Construct an Http Denied Exception.
     * 
     * @param string $message
     * @param Throwable $e
     * @param int $code
     * @param array $headers
     */
    public function __construct(string $message, Throwable $e, int $code = 0, array $headers = [])
    {
        parent::__construct(403, $message, $e, $headers, $code);
    }
}
