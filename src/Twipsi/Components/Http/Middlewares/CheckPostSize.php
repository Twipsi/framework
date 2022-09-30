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
use Twipsi\Components\Http\Exceptions\InvalidPostSizeException;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Support\Str;

class CheckPostSize implements MiddlewareInterface
{
  /**
  * Resolve middleware logics.
  */
  public function resolve( Request $request, ...$args ) : Closure|bool
  {
    $size = $this->getPostSize();

    if( $size !== 0 && $size < $request->headers->get( 'content-length' ) ) {
      throw new InvalidPostSizeException( "The recieved post size is too large (increase size in php ini)." );
    }

    return true;
  }

  /**
  * Get and convert php ini postsize
  */
  protected function getPostSize() : int
  {
    $php = ini_get('post_max_size');

    if (Str::hay($php)->numeric()) {
      return (int)$php;
    }

    switch (Str::hay($php)->last()) {
      case 'T' :
        return (int)$php * 1099511627776;
      case 'G' :
        return (int)$php * 1073741824;
      case 'M' :
        return (int)$php * 1048576;
      case 'K' :
        return (int)$php * 1024;
      default:
        return (int)$php;
    }
  }

}
