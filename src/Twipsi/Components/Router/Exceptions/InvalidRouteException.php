<?php

namespace Twipsi\Components\Router\Exceptions;

use InvalidArgumentException;
use Twipsi\Components\Http\Exceptions\HttpException;

final class InvalidRouteException extends HttpException
{
    /**
     * Construct Exception.
     *
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct(500, $message, $previous);
    }

}
