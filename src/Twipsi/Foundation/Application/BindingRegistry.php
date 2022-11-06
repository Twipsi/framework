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

namespace Twipsi\Foundation\Application;

use Closure;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Bags\ArrayBag as Container;

class BindingRegistry extends Container
{
  /**
  * Singleton abstracts container.
  */
  public array $singletons = [];

  /**
  * Rebindings container.
  */
  public array $rebindings = [];

  /**
  * Extension container.
  */
  public array $extensions = [];

  /**
  * Bind a concrete closure to an abstract.
  */
  public function bind(string $abstract, string|Closure $concrete = null, bool $singleton = false) : void
  {
    // If singleton is true we will set the instance to be saved.
    if ($singleton) {
      $this->singletons[] = $abstract;
    }

    // If there is no concrete set, use the abstract.
    if (null === $concrete) {
      $concrete = $abstract;
    }

    // If the concrete is not valid or class does not exist exit.
    if (is_string($concrete) && !class_exists($concrete)) {
      throw new ApplicationManagerException(sprintf("The provided concrete class does not exist {%s}", $concrete));
    }

    $this->set($abstract, $concrete);
  }

  public function rebind(string $abstract, Closure $callback): void
  {
    $this->rebindings[$abstract][] = $callback;
  }

  public function extend(string $abstract, Closure $callback): void
  {
    $this->extensions[$abstract][] = $callback;
  }

  /**
  * Check if an abstract is set to be persistent singleton.
  */
  public function isPersistent(string $abstract) : bool
  {
    return in_array($abstract, $this->singletons);
  }

  public function rebindings(string $abstract): array
  {
    return isset($this->rebindings[$abstract]) ? $this->rebindings[$abstract] : [];
  }

}
