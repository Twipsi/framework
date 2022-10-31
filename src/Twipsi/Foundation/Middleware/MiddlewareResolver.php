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

use Closure;
use Twipsi\Components\Http\Exceptions\HttpResponseException;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Middleware\Exceptions\InvalidMiddlewareException;
use Twipsi\Support\Bags\ArrayBag as Container;

class MiddlewareResolver
{
  /**
  * Middleware subscriber.
  */
  var MiddlewareSubscriber $subscriber;

  /**
  * Application instance.
  */
  var Application $app;

  /**
  * Middleware response hooks.
  */
  var Container $hooks;

  /**
  * Construct our middleware resolver.
  */
  public function __construct(Application $application, MiddlewareSubscriber $subscriber)
  {
    $this->app = $application;
    $this->subscriber = $subscriber;
    $this->hooks = new Container;
  }

  /**
  * Resolve middleware collection.
  */
  public function resolve(MiddlewareCollector $collection) : Container
  {
    if ($collection->has('general')) {
      $this->resolveGeneralMiddlewares($collection->get('general'));
    }

    if ($collection->has('group')) {
      $this->resolveGroupMiddlewares($collection->get('group'));
    }

    if ($collection->has('single')) {
      $this->resolveSingleMiddlewares($collection->get('single'));
    }

    if ($collection->has('custom')) {
      $this->resolveCustomMiddlewares($collection->get('custom'));
    }

    return $this->hooks;
  }

  /**
  * Dispatch requested middleware
  */
  protected function dispatch(string|array $middleware) : ResponseInterface|Closure|bool
  {
    // If it is a single or custom check for custom arguments.
    if (is_array($middleware)) {
      [$middleware, $args] = $middleware;
    }

    // Make class through the application.
    $middleware = $this->app->make($middleware);

    // If its not a valid middleware interface exit.
    if (! $middleware instanceof MiddlewareInterface) {
      throw new InvalidMiddlewareException(
        sprintf("Middleware: [%s] does not implement 'MiddlewareInterface'", get_class($middleware))
      );
    }

    if (isset($args) && is_array($args)) {
      return $middleware->resolve($this->app->get( 'request' ), ...$args);
    }

    return $middleware->resolve($this->app->get( 'request' ));
  }

  /**
  * Resolve middlewares and handle result.
  */
  protected function handle(array $middlewares) : void
  {
    foreach ($middlewares as $middleware) {

      // If the middleware returns a closure add it the hooks for later processing.
      if(($resolved = $this->dispatch($middleware)) instanceof Closure ) {
        $this->hooks->set(is_array($middleware) ? $middleware[0] : $middleware, $resolved);
      }

      if($resolved instanceof ResponseInterface) {
        throw new HttpResponseException($resolved);
      }
    }
  }

  /**
  * Resolve general middlewares.
  */
  protected function resolveGeneralMiddlewares(array $middlewares) : void
  {
    $this->handle($middlewares);
  }

  /**
  * Resolve group middlewares.
  */
  protected function resolveGroupMiddlewares(array $middlewares) : void
  {
    $this->handle($middlewares);
  }

  /**
  * Resolve single middlewares.
  */
  protected function resolveSingleMiddlewares(array $middlewares) : void
  {
    $this->handle($middlewares);
  }

  /**
  * Resolve custom middleware.
  */
  protected function resolveCustomMiddlewares(array $middlewares) : void
  {
    $this->handle($middlewares);
  }

}
