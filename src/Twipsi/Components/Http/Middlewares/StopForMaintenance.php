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
use Twipsi\Components\Http\Exceptions\IsDownException;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\Middlewares\Tools\ExceptionChecker;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class StopForMaintenance implements MiddlewareInterface
{
  /**
  * Exception list of urls
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

    if( !config( 'system.maintenance' ) ) {
      return true;
    }

    // Load template once we have our views done!!!
    throw new IsDownException( 'Service down for maintanance', 503 );
  }
}
