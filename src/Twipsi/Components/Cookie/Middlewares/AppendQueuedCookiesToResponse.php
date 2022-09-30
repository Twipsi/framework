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

namespace Twipsi\Components\Cookie\Middlewares;

use Closure;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\Response\Response;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class AppendQueuedCookiesToResponse implements MiddlewareInterface
{
  /**
  * Resolve middleware logics.
  */
  public function resolve(Request $request, mixed ...$args) : Closure|bool
  {
    // Append Cookies closure for response
    return $this->appendQueuedCookiesToResponse(...);
  }

  /**
  * Append all the queued cookies to response headers.
  */
  public function appendQueuedCookiesToResponse(Request $request, Response $response) : Response
  {
    foreach($request->cookies()->getQueuedCookies() as $cookie) {
      $response->headers->setCookie($cookie);
    }

    return $response;
  }

}
