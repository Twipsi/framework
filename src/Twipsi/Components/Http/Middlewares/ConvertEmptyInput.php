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
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\InputBag;
use Twipsi\Components\Http\Middlewares\Tools\ExceptionChecker;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class ConvertEmptyInput implements MiddlewareInterface
{
  /**
  * Exception list of keys.
  */
  protected array $exceptionKeys = [];

  /**
  * Exception list of urls.
  */
  protected array $exceptionUrls = [];

  /**
  * Resolve middleware logics.
  */
  public function resolve( Request $request, ...$args ) : Closure|bool
  {
    if( ExceptionChecker::url( $request->url(), $this->exceptionUrls ) ) {
      return true;
    }

    if( $request->isRequestMethod( 'POST' ) ) {
      $this->normalize( $request->request );
    }

    if( $request->isRequestMethod( 'GET' ) ) {
      $this->normalize( $request->query );
    }

    return true;
  }

  /**
  * Normalize the input bag with our new data.
  */
  protected function normalize( InputBag $bag ) : void
  {
    $cleaned = $this->clean( $bag->all( ...$this->exceptionKeys ) );
    $bag->override( $bag->merge( $cleaned )->all() );
  }

  /**
  * Iterate data and attempt to convert them.
  */
  protected function clean( array $data ) : array
  {
    array_walk_recursive( $data, function( &$value ) {
      $value = is_array( $value ) ? $this->clean( $value ) : $this->convert( $value );
    });

    return $data;
  }

  /**
  * Convert empty strings to null.
  */
  protected function convert( mixed $value ) : mixed
  {
    return is_string( $value ) && '' === $value ? null : $value;
  }

}
