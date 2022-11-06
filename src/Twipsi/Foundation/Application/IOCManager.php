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
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Bags\ObjectBag;

class IOCManager implements \ArrayAccess
{
  use ApplicationAccessor;

  /**
  * System alias container.
  */
  protected AliasRegistry $aliases;

  /**
  * System instance container.
  */
  protected InstanceRegistry $instances;

  /**
  * System bindings container.
  */
  protected BindingRegistry $bindings;

  /**
  * System parameter bindings container.
  */
  protected ImplantRegistry $implants;

  /**
   * Resolved abstracts container.
   *
   * @var array
   */
  protected array $resolved = [];

  /**
  * Application Manager constructor.
  */
  public function __construct()
  {
    $this->aliases = new AliasRegistry;
    $this->instances = new InstanceRegistry;
    $this->bindings = new BindingRegistry;
    $this->implants = new ImplantRegistry;
  }

  /**
  * Register a persistable binding.
  */
  public function keep(string $abstract, string|Closure $concrete = null) : void
  {
    $this->bind($abstract, $concrete, true);
  }

  /**
  * Attempt to load or build instance based on provided abstract.
  */
  public function make(string $abstract, array $parameters = []) : mixed
  {
    // If we dont have any custom parameters we can return the resolved instance.
    if (! $parameters) {

      // If we have already resolved the abstract, we will return the same instance.
      if ($this->instances->has($abstract)) {

        return $this->instances->get($abstract);
      }
    }

    // If we have any bound parameters registered for the abstract, use them.
    if ($this->implants->has($abstract)) {
      $parameters = array_merge($this->implants->get($abstract), $parameters);
    }

    // Get the concrete for the abstract.
    $concrete = $this->concrete($abstract);

    // If the provided concrete is not buildable exit.
    if (! $this->isBuildable($concrete)) {
      throw new ApplicationManagerException(sprintf('The requested instance can not be built {%s}', $concrete));
    }

    // Build the instance and resolve all its dependencies.
    $instance = $this->build($concrete, $parameters);

    // Append all the applicables.
    if (is_object($instance)) {
      $instance = $this->resolveClassApplicables($instance);
    }

    if(isset($this->bindings->extensions[$abstract])) {
      foreach($this->bindings->extensions[$abstract] as $extension) {
        $extension($this, $instance);
      }
    }

    // Register the newly created instance so it can be accessed later.
    if ($this->isPersistent($abstract)) {

      $this->instances->set($abstract, $instance);
    }

    $this->resolved[$abstract] = $instance;

    return $instance;
  }

  /**
  * Attempt to build a class and resolve dependencies.
  */
  public function build(string|Closure $concrete, array $parameters = []) : mixed
  {
    // If a closure was provided then run it.
    if ($concrete instanceof Closure) {
      return $this->resolveClosureDependencies($concrete, $parameters);
    }

    // Set up reflection class.
    $reflection = new ObjectBag($concrete);

    // If the class can not be instantiated throw an error.
    if (! $reflection->isInstantiable()) {
      throw new ApplicationManagerException(sprintf('The requested instance can not be instantiated {%s}', $concrete));
    }

    // If the class has no constructor, we dont need to do anything else.
    if (! $reflection->hasConstructor()) {
      return new $concrete;
    }

    // Resolve the required dependencies.
    $dependencies = (new DependencyResolver($this, $parameters))
        ->resolve($reflection->getDependencies());

    return $reflection->instantiateWithParameters($dependencies ?? []);
  }

    /**
     * Call for a registered instance method.
     * @throws ReflectionException
     * @throws ApplicationManagerException
     */
    public function call(string|object $abstract, string $method, array $parameters = []) : mixed
    {
        $concrete = is_string($abstract) ? $this->concrete($abstract) : get_class($abstract);

        $reflection = new ReflectionMethod($concrete, $method);
        $dependencies = (new DependencyResolver($this, $parameters))
            ->resolve($reflection->getParameters());

        $object = is_string($abstract) ? $this->make($abstract) : $abstract;

        return $object ? $object->{$method}($dependencies) : null;
    }

  /**
  * Resolve class applicables.
  */
  protected function resolveClassApplicables(object $object) : object
  {
    // If we have any traits attempt to find applicables.
    if ($traits = (new ReflectionClass($object))->getTraits()) {

      $applicables = (new DependencyResolver($this))->resolveApplicables($traits);

      // Call the append method on all applicables.
      foreach ($applicables ?? [] as $closure) {
        $object = $closure($object);
      }
    }

    return $object;
  }

  /**
  * Resolve closure dependecies.
  */
  protected function resolveClosureDependencies(Closure $closure, array $parameters = []) : mixed
  {
    $reflection = new ReflectionFunction($closure);

    // If we have no dependencies just run the closure.
    if (! $dependecies = $reflection->getParameters()) {
      return $closure();
    }

    $dependencies = (new DependencyResolver($this, $parameters))->resolve($dependecies);

    return $closure(...$dependencies);
  }

  /**
  * Get the concrete for the requested abstract.
  */
  public function concrete(string $abstract) : string|Closure
  {
    // Attempt to search for the concrete in post bound
    // registry and use it instead of the one in the alias registry.
    if ($this->bindings->has($abstract)) {
      return $this->bindings->get($abstract);
    }

    // Attempt to search for the concrete in the alias
    // container and use it to build the instance.
    if ($this->aliases->has($abstract)) {
      return $this->aliases->get($abstract);
    }

    return $abstract;
  }

  /**
  * Check if the concrete is buildable.
  */
  public function isBuildable(string|Closure $concrete) : bool
  {
    if ($concrete instanceof Closure || class_exists($concrete)) {
      return true;
    }

    return false;
  }

  /**
  * Bind a parameter dependency to an abstract.
  */
  public function implant(string $abstract, array $parameters = []) : void
  {
    $abstract = $this->getAlias($abstract);

    $this->instances->delete($abstract);
    $this->implants->bind($abstract, $parameters);
  }

  /**
  * Bind a concrete closure or class to an abstract.
  */
  public function bind(string $abstract, string|Closure $concrete = null, bool $persistent = false) : void
  {
    $abstract = $this->getAlias($abstract);

    $this->instances->delete($abstract);
    $this->bindings->bind($abstract, $concrete, $persistent);

    // If the abstract was already resolved then
    // return the instance with the binding applied.
    if($this->isResolved($abstract)) {
      $this->rebuild($abstract);
    }
  }

  /**
   * Rebind an abstract with a callback.
   *
   * @param string $abstract
   * @param Closure $callback
   *
   * @return void
   */
  public function rebind(string $abstract, Closure $callback) : void
  {
    $abstract = $this->getAlias($abstract);

    $this->bindings->rebind($abstract, $callback);

    // If the abstract was already resolved then
    // return the instance with the binding applied.
    if($this->isResolved($abstract)) {
      $this->rebuild($abstract);
    }
  }

  public function extend(string $abstract, Closure $callback): void
  {
    $abstract = $this->getAlias($abstract);

    $this->bindings->extend($abstract, $callback);

    if($this->isResolved($abstract)) {
      $this->rebuild($abstract);
    }
  }

  /**
  * Bind an instance to an abstract.
  */
  public function instance(string $abstract, object $instance) : object
  {
    $abstract = $this->getAlias($abstract);

    $bound = $this->bound($abstract);

    // Set the instance to the abstract.
    $this->instances->bind($abstract, $instance);

    if($bound) {
      $this->rebuild($abstract);
    }

    return $instance;
  }

  /**
  * Check if an abstract is bound in the application.
  */
  public function bound(string $abstract) : bool
  {
    return $this->bindings->has($abstract) ||
           $this->instances->has($abstract) ||
           $this->isAlias($abstract);
  }

  /**
   * Rebuild the abstract.
   *
   * @param string $abstract
   *
   * @return void
   */
  public function rebuild(string $abstract): void
  {
    $instance = $this->make($abstract);

    foreach ($this->getRebindings($abstract) as $callback) {
        $callback($this, $instance);
    }
  }

  /**
  * Find alias pointer for an abstract.
  */
  public function getAlias(string $abstract) : string
  {
    return $this->aliases->resolve($abstract);
  }

  /**
  * Check if abstract is an alias.
  */
  public function isAlias(string $abstract) : bool
  {
    return $this->aliases->alias($abstract);
  }

  public function isResolved(string $abstract): bool
  {
    return $this->instances->has($abstract) || isset($this->resolved[$abstract]);
  }

  public function getRebindings(string $abstract) : array
  {
    return $this->bindings->rebindings($abstract);
  }

  /**
  * Check if an abstract is set to be persistent singleton.
  */
  public function isPersistent(string $abstract) : bool
  {
    return $this->instances->has($abstract) ||
           $this->bindings->isPersistent($abstract);
  }

}
