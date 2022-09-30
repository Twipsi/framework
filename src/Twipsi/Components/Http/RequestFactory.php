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

namespace Twipsi\Components\Http;

use Twipsi\Components\Http\HttpRequest;

class RequestFactory extends HttpRequest
{
  /**
  * Create an http request based on provided data.
  */
  public static function create(array $headers = [], array $get = [], array $post = [], array $files = [], array $cookies = [], array $properties = []) : HttpRequest
  {
    return new self($headers, $get, $post, $files, $cookies, $properties);
  }

  /**
  * Create an http request based on globals.
  */
  public static function fromGlobals(array $properties = []) : HttpRequest
  {
    return self::create($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $properties);
  }
}
