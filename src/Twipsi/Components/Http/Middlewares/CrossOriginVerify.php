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

namespace Twipsi\Components\Http\Middlewares;

use Closure;
use Twipsi\Components\Http\Exceptions\InvalidOriginException;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Support\Arr;

class CrossOriginVerify implements MiddlewareInterface
{
  /**
  * Accepted CORS origins
  */
  protected array $acceptedOrigins = [];

  /**
  * Resolve middleware logics.
  */
  public function resolve( Request $request, ...$args ) : Closure|bool
  {
    $origin = $request->headers->get( 'http-origin' );

    if( empty( $this->acceptedOrigins ) ) {
      return true;
    }

    if( !Arr::hay( $this->acceptedOrigins )->contains( $origin ) ) {
      throw new InvalidOriginException( 'Cross-Origin Request Blocked: The server does not accept requests from origin "'.$origin.'"', 400 );
    }

    return true;
  }

}
