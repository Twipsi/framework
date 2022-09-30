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

class MiddlewareSubscriber
{
  /**
  * General middlewares container.
  */
  protected array $generalMiddlewares;

  /**
  * Group middlewares.
  */
  protected array $groupMiddlewares;

  /**
  * Single middlewares.
  */
  protected array $singleMiddlewares;

  /**
  * Construct middleware subscriber.
  */
  public function __construct(array $general, array $group, array $single)
  {
    $this->generalMiddlewares  = $general;
    $this->groupMiddlewares    = $group;
    $this->singleMiddlewares   = $single;
  }

  /**
  * Return general middlewares.
  */
  public function getGeneralMiddlewares() :? array
  {
    return $this->generalMiddlewares;
  }

  /**
  * Return a group from group middlewares.
  */
  public function getGroupMiddlewares(string $name) :? array
  {
    return $this->groupMiddlewares[$name] ?? null;
  }

  /**
  * Return a middleware from route middlewares.
  */
  public function getSingleMiddleware(string $name) :? string
  {
    return $this->singleMiddlewares[$name] ?? null;
  }

  /**
  * Check if middleware group exists.
  */
  public function hasGroupMiddleware(string $name) : bool
  {
    return isset($this->groupMiddlewares[$name]);
  }

  /**
  * Check if single middleware exists.
  */
  public function hasSingleMiddleware(string $name) : bool
  {
    return isset($this->singleMiddlewares[$name]);
  }

}
