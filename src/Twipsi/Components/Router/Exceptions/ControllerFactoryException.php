<?php

namespace Twipsi\Components\Router\Exceptions;

final class ControllerFactoryException extends \RuntimeException
{
    /**
     * Construct exception.
     *
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
