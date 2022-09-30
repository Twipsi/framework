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

trait ApplicationAccessor
{
  /**
  * Send direct get request through call method
  */
  public function __get(string $instance) : mixed
  {
    if(method_exists($this, $instance)) {
      return $this->{$instance};
    }

    return $this->call($instance);
  }

  /**
  * Get instance with get method
  */
  public function get($instance) : mixed
  {
    return $this->call($instance);
  }

  /**
  * Check if offset exists
  */
  public function offsetExists(mixed $key) : bool
  {
    return $this->instances->has($key);
  }

  /**
  * Get offset value
  */
  public function offsetGet(mixed $key) : mixed
  {
    return $this->call($key);
  }

  /**
  * Set offset value
  */
  public function offsetSet(mixed $key, mixed $value) : void
  {
    $this->instances->set($key, $value);
  }

  /**
  * Delete offset
  */
  public function offsetUnset(mixed $key) : void
  {
    $this->instances->delete($key);
  }

}
