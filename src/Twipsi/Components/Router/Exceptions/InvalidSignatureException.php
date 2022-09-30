<?php

namespace Twipsi\Components\Router\Exceptions;

final class InvalidSignatureException extends \Exception
{
    /**
     * Construct Exception
     */
    public function __construct()
    {
        parent::__construct('Invalid signature found.', 403);
    }
}
