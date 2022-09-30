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

namespace Twipsi\Facades;

use Closure;
use RuntimeException;
use Twipsi\Foundation\Application\Application;

class Facade
{
  /**
  * Loaded objects.
  */
  private static array $loaded = [];

  /**
  * Bypassable facades.
  */
  private static array $bypassable = [];

  /**
  * The framework instance.
  */
  private static Application $application;

  /**
  * Swap loaded instance with another one specified.
  */
  public static function swap(object $object) : static
  {
    static::$loaded[static::getFacadeAccessor()] = $object;
    return new self;
  }

  /**
  * Reset the facade instance.
  */
  public static function reset() : static
  {
    static::deleteLoadedInstance(static::getFacadeAccessor());
    return new self;
  }

  /**
  * Return the facade class.
  */
  public static function class() :? object
  {
    return static::resolveFacadeInstance();
  }

  /**
  * Set facade to bypass application inheritance.
  */
  public static function new() :? object
  {
    static::$bypassable[static::getFacadeAccessor()] = true;
    return new self;
  }

  /**
  * Set the application framework.
  */
  public static function setFacadeApplication(Application $application) : void
  {
    static::$application = $application;
  }

  /**
  * Return the requested accessor name.
  */
  public static function getFacadeAccessor() : string
  {
    if (! method_exists(static::class, 'getFacadeAccessorName')) {
      throw new RuntimeException("Facade does not implement a vital method [getFacadeAccessorName]");
    }

    return static::getFacadeAccessorName();
  }

  /**
  * Return the requested accessor class.
  */
  public static function getFacadeClass() : string
  {
    if (! method_exists(static::class, 'getFacadeClassName')) {
      throw new RuntimeException("Facade does not implement a vital method [getFacadeClassName]");
    }

    return static::getFacadeClassName();
  }

  /**
  * Resolve and return an instantiated object of the facade.
  */
  protected static function resolveFacadeInstance() :? object
  {
    return static::loadFacadeInstance(static::getFacadeAccessor());
  }

  public static function getFacadeAccessorName(): string 
  {
    return '';
  }

  public static function getFacadeClassName(): string 
  {
    return '';
  }

  /**
  * Load the requested facade class.
  */
  protected static function loadFacadeInstance(object|string $class) :? object
  {
    if (is_object($class)) {
      return $class;
    }

    if (array_key_exists($class, static::$bypassable)) {

      static::deleteBypassableInstance($class);

      if (class_exists($object = static::getFacadeClass()))  {
        return static::$loaded[$class] = new $object;

      } else {
        throw new RuntimeException("The requested class could not be loaded");
      }

    }

    if (null !== $object = static::getLoadedInstance($class)) {
      return $object;
    }

    if (static::$application)  {
      return static::$loaded[$class] = static::$application->{$class};
    }

    return null;
  }

  /**
  * Delete a laoded instance from the container.
  */
  protected static function deleteLoadedInstance(string $name) : void
  {
    unset(static::$loaded[$name]);
  }

  /**
  * Delete a bypassable instance from the container.
  */
  protected static function deleteBypassableInstance(string $name) : void
  {
    unset(static::$bypassable[$name]);
  }

  /**
  * Get a laoded instance from the container.
  */
  protected static function getLoadedInstance(string $name) : ?object
  {
    if (array_key_exists($name, static::$loaded)) {
      return static::$loaded[$name];
    }

    return null;
  }

  /**
   * Clear all the loaded instances.
   * 
   * @return void
   */
  public static function clearResolvedInstances(): void
  {
    static::$loaded = [];
  }

  /**
  * Call a facade staticly.
  */
  public static function __callStatic(string $method, array $args) : mixed
  {
    $instance = static::resolveFacadeInstance();

    if (is_null($instance)) {
      throw new RuntimeException(sprintf('Cannot resolve the facade accessor [%s]', static::getFacadeAccessor()));
    }

    if ($instance instanceof Closure) {
      return $instance(...$args);
    }

    if (! method_exists($instance, $method)) {

      if (! is_callable([$instance, $method])) {
        throw new RuntimeException("Facade class [".get_class($instance)."] does not implement the requested method [$method]");
      }
    }

    return $instance->$method(...$args);
  }

  /**
  * Call a facade with "->" operator.
  */
  public function __call(string $method, array $args) : mixed
  {
    return static::__callStatic($method, $args);
  }

}
