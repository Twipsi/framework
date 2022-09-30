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

use Twipsi\Components\Router\Route\Route;
use Twipsi\Foundation\Application\Application;
use Twipsi\Support\Bags\RecursiveArrayBag as Container;

class MiddlewareHandler
{
  /**
  * Middleware subscriber.
  */
  protected MiddlewareSubscriber $subscriber;

  /**
  * Middleware resolver.
  */
  protected MiddlewareResolver $resolver;

  /**
  * Middleware collector.
  */
  protected MiddlewareCollector $collector;

  /**
  * Construct our middleware handler.
  */
  public function __construct(Application $application, array $middlewares)
  {
    $this->subscriber = new MiddlewareSubscriber(...$middlewares);
    $this->collector = new MiddlewareCollector($this->subscriber);

    $this->resolver = new MiddlewareResolver($application, $this->subscriber);
  }

  /**
  * Return middleware resolver.
  */
  public function resolve(MiddlewareCollector $collection) : Container
  {
    return $this->resolver->resolve($collection);
  }

  /**
  * Collect all middlewares for route.
  */
  public function collect(Route $route) : MiddlewareCollector
  {
    return $this->collector->collect($route);
  }

}
