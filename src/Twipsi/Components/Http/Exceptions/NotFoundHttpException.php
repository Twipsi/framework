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

class NotFoundHttpException extends HttpException
{
    /**
     * Construct an Http Not Found Exception.
     * 
     * @param string $message
     * @param Throwable|null $e
     * @param int $code
     * @param array $headers
     */
    public function __construct(string $message, Throwable $e = null, int $code = 0, array $headers = [])
    {
        parent::__construct(404, $message, $e, $headers, $code);
    }
}
