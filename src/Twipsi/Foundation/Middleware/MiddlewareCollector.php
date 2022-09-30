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

namespace Twipsi\Foundation\Middleware;

use RuntimeException;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Support\Bags\RecursiveArrayBag as Container;
use Twipsi\Support\Str;

class MiddlewareCollector extends Container
{
  /**
  * Middleware subscriber.
  */
  protected MiddlewareSubscriber $subscriber;

  /**
  * Construct our middleware collector.
  */
  public function __construct(MiddlewareSubscriber $subscriber)
  {
    $this->subscriber = $subscriber;
  }

  /**
  * Collect all the middlewares needed to be executed
  */
  public function collect(Route $route) : MiddlewareCollector
  {
    // Collect all the general middlewares.
    $this->set('general', $this->collectGeneralMiddlewares());

    // Collect all group and route middlewares applied.
    foreach ($this->collectRouteMiddlewares($route) as $middleware) {

      $arguments = null;

      // If the middleware is registered as a group, then
      // add all the middlewares to the stack registered in the group.
      if ($this->subscriber->hasGroupMiddleware($middleware)) {

        foreach ($this->subscriber->getGroupMiddlewares($middleware) as $key => $value) {
          $this->push('group', $value);
        }

        continue;
      }

      // If its a single or custom middleware try custom arguments.
      if (Str::hay($middleware)->contains('@')) {
        [$middleware, $arguments] = explode('@', $middleware);
        $arguments = explode(',', $arguments);
      }

      // If the middleware is a single route middleware, then
      // add it to the stack with the custom arguments.
      if ($this->subscriber->hasSingleMiddleware($middleware)) {
        $this->push('single',
                    [$this->subscriber->getSingleMiddleware($middleware), $arguments ?? null]);
        continue;
      }

      // If the middleware is a custom callable class, then
      // add it to the stack with the custom arguments.
      if (class_exists($middleware)) {
        $this->push('custom', [$middleware, $arguments ?? null]);
        continue;
      }

      throw new RuntimeException(sprintf('Middleware [%s] could not be found', $middleware));
    }

    return $this;
  }

  /**
  * Collect all the gerneral middlewares.
  */
  public function collectGeneralMiddlewares() : array
  {
    return $this->subscriber->getGeneralMiddlewares();
  }

  /**
  * Collect all the route and route groupo middlewares.
  */
  public function collectRouteMiddlewares(Route $route) : array
  {
    return $route->getMiddlewares();
  }

}
