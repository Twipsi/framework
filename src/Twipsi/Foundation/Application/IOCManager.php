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

use ArrayAccess;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Bags\ObjectBag;

class IOCManager implements ArrayAccess
{
    use ApplicationAccessor;

    /**
     * System alias container.
     *
     * @var AliasRegistry
     */
    protected AliasRegistry $aliases;

    /**
     * System instance container.
     *
     * @var InstanceRegistry
     */
    protected InstanceRegistry $instances;

    /**
     * System bindings container.
     *
     * @var BindingRegistry
     */
    protected BindingRegistry $bindings;

    /**
     * System parameter bindings container.
     *
     * @var ImplantRegistry
     */
    protected ImplantRegistry $implants;

    /**
     * Resolved abstracts' container.
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
     * Register a persisting singleton binding.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function keep(string $abstract, string|Closure $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind a concrete closure or class to an abstract.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $persistent
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function bind(string $abstract, string|Closure $concrete = null, bool $persistent = false): void
    {
        $abstract = $this->getAlias($abstract);

        // We will delete any resolved instances when binding.
        $this->instances->delete($abstract);

        $this->bindings->bind($abstract, $concrete, $persistent);

        // If the abstract was already resolved then we will return
        // the instance while applying any rebindings registered.
        if ($this->isResolved($abstract)) {
            $this->rebuild($abstract);
        }
    }

    /**
     * Attempt to load or build instance based on provided abstract.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws ApplicationManagerException|ReflectionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // If we don't have any custom parameters we can return the resolved instance.
        if (!$parameters) {

            // If we have already resolved the abstract, we will return the same instance
            // since all bindings rebindings and extension were already applied.
            if ($this->instances->has($abstract)) {
                return $this->instances->get($abstract);
            }
        }

        // If we have any implanted custom parameters registered
        // for a specific abstract to use when resolving dependencies, use them.
        if ($this->implants->has($abstract)) {
            $parameters = array_merge($this->implants->get($abstract), $parameters);
        }

        // Get the concrete for the abstract.
        $concrete = $this->concrete($abstract);

        // If the provided concrete is not buildable exit.
        if (!$this->isBuildable($concrete)) {
            throw new ApplicationManagerException(
                sprintf('The requested instance can not be built {%s}', $concrete)
            );
        }

        // Build the instance and resolve all its dependencies.
        $instance = $this->build($concrete, $parameters);

        // Append all the applicables to the instance.
        // This will inject and initiate any components requested by applicables.
        if (is_object($instance)) {
            $instance = $this->resolveClassApplicables($instance);
        }

        // Execute any extensions registered for the abstract.
        if (isset($this->bindings->extensions[$abstract])) {
            foreach ($this->bindings->extensions[$abstract] as $extension) {
                $extension($this, $instance);
            }
        }

        // Register the newly created instance, so it can be accessed later
        // without having to re-initialize it.
        if ($this->isPersistent($abstract)) {
            $this->instances->set($abstract, $instance);
        }

        // Register the instance to the resolved collection.
        $this->resolved[$abstract] = $instance;

        return $instance;
    }

    /**
     * Resolve a method in a class using dependency injection.
     *
     * @param string|object $abstract
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function call(string|object $abstract, string $method, array $parameters = []): mixed
    {
        $concrete = is_string($abstract) ? $this->concrete($abstract) : get_class($abstract);

        $reflection = new ReflectionMethod($concrete, $method);
        $dependencies = (new DependencyResolver($this, $parameters))
            ->resolve($reflection->getParameters());

        $object = is_string($abstract) ? $this->make($abstract) : $abstract;

        if (empty($dependencies)) {
            return $object ? $object->{$method}() : null;
        }

        return $object ? $object->{$method}(...$dependencies) : null;
    }

    /**
     * Attempt to build a class and resolve dependencies.
     *
     * @param string|Closure $concrete
     * @param array $parameters
     * @return mixed
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function build(string|Closure $concrete, array $parameters = []): mixed
    {
        // If a closure was provided then run it.
        if ($concrete instanceof Closure) {
            return $this->resolveClosureDependencies($concrete, $parameters);
        }

        // Set up the reflection class for the concrete.
        $reflection = new ObjectBag($concrete);

        // If the class can not be instantiated throw an error.
        if (!$reflection->isInstantiable()) {
            throw new ApplicationManagerException(
                sprintf('The requested instance can not be instantiated {%s}', $concrete)
            );
        }

        // If the class has no constructor, we don't need to do anything else.
        if (!$reflection->hasConstructor()) {
            return new $concrete;
        }

        // Resolve the required dependencies.
        $dependencies = (new DependencyResolver($this, $parameters))
            ->resolve($reflection->getDependencies());

        return $reflection->instantiateWithParameters($dependencies ?? []);
    }

    /**
     * Resolve closure dependencies.
     *
     * @param Closure $closure
     * @param array $parameters
     * @return mixed
     * @throws ReflectionException
     */
    protected function resolveClosureDependencies(Closure $closure, array $parameters = []): mixed
    {
        $reflection = new ReflectionFunction($closure);

        // If we have no dependencies just run the closure.
        if (!$dependencies = $reflection->getParameters()) {
            return $closure();
        }

        $dependencies = (new DependencyResolver($this, $parameters))
            ->resolve($dependencies);

        return $closure(...$dependencies);
    }

    /**
     * Resolve class applicables.
     *
     * @param object $object
     * @return object
     */
    protected function resolveClassApplicables(object $object): object
    {
        // If we have any traits attempt to find applicables.
        if ($traits = (new ReflectionClass($object))->getTraits()) {

            $applicables = (new DependencyResolver($this))
                ->resolveApplicables($traits);

            // Call the append method on all applicables.
            foreach ($applicables ?? [] as $closure) {
                $object = $closure($object);
            }
        }

        return $object;
    }

    /**
     * Get the concrete for the requested abstract.
     *
     * @param string $abstract
     * @return string|Closure
     */
    public function concrete(string $abstract): string|Closure
    {
        // Attempt to search for the concrete in post bound registry
        // and use it instead of the one in the alias registry.
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
     *
     * @param string|Closure $concrete
     * @return bool
     */
    public function isBuildable(string|Closure $concrete): bool
    {
        return $concrete instanceof Closure || class_exists($concrete);
    }

    /**
     * Find the alias name for an abstract.
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        return $this->aliases->resolve($abstract);
    }

    /**
     * Check if we have already resolved an abstract.
     *
     * @param string $abstract
     * @return bool
     */
    public function isResolved(string $abstract): bool
    {
        return $this->instances->has($abstract)
            || isset($this->resolved[$abstract]);
    }

    /**
     * Rebuild the abstract applying all rebindings.
     *
     * @param string $abstract
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function rebuild(string $abstract): void
    {
        $abstract = $this->getAlias($abstract);

        $this->instances->delete($abstract);

        $instance = $this->make($abstract);

        foreach ($this->bindings->rebindings($abstract) as $callback) {
            $callback($this, $instance);
        }
    }

    /**
     * Bind a parameter dependency to an abstract.
     *
     * @param string $abstract
     * @param array $parameters
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function implant(string $abstract, array $parameters = []): void
    {
        $abstract = $this->getAlias($abstract);

        $this->implants->bind($abstract, $parameters);

        // If the abstract was already resolved then
        // return the instance with the binding applied.
        if ($this->isResolved($abstract)) {
            $this->rebuild($abstract);
        }
    }

    /**
     * Rebind an abstract with a callback.
     *
     * @param string $abstract
     * @param Closure $callback
     * @return void
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function rebind(string $abstract, Closure $callback): void
    {
        $abstract = $this->getAlias($abstract);

        $this->bindings->rebind($abstract, $callback);

        // If the abstract was already resolved then
        // return the instance with the binding applied.
        if ($this->isResolved($abstract)) {
            $this->rebuild($abstract);
        }
    }

    /**
     * Extend an abstract with a callback.
     *
     * @param string $abstract
     * @param Closure $callback
     * @return void
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function extend(string $abstract, Closure $callback): void
    {
        $abstract = $this->getAlias($abstract);

        $this->bindings->extend($abstract, $callback);

        if ($this->isResolved($abstract)) {
            $this->rebuild($abstract);
        }
    }

    /**
     * Bind an instance to an abstract.
     *
     * @param string $abstract
     * @param object $instance
     * @return object
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function instance(string $abstract, object $instance): object
    {
        $abstract = $this->getAlias($abstract);

        $bound = $this->bound($abstract);

        // Set the instance to the abstract.
        $bound
            ? $this->rebuild($abstract)
            : $this->instances->bind($abstract, $instance);

        return $instance;
    }

    /**
     * Check if an abstract is bound in the application.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return $this->bindings->has($abstract)
            || $this->instances->has($abstract)
            || $this->isAlias($abstract);
    }

    /**
     * Check if abstract is an alias.
     *
     * @param string $abstract
     * @return bool
     */
    public function isAlias(string $abstract): bool
    {
        return $this->aliases->alias($abstract);
    }

    /**
     * Check if an abstract is set to be persistent singleton.
     *
     * @param string $abstract
     * @return bool
     */
    public function isPersistent(string $abstract): bool
    {
        return $this->instances->has($abstract) ||
            $this->bindings->isPersistent($abstract);
    }
}
