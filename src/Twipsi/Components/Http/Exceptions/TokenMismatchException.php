<?php

namespace Twipsi\Components\Http\Exceptions;

use Exception;

class TokenMismatchException extends Exception
{
  public function __construct(string $message, int $code)
  {
    parent::__construct($message, $code);
  }
}
