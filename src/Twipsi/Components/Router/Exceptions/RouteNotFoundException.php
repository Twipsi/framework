<?php

namespace Twipsi\Components\Router\Exceptions;

final class RouteNotFoundException extends \InvalidArgumentException
{
    /**
     * Construct 404 exception.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}
