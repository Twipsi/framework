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

abstract class Facade
{
    /**
     * Loaded objects.
     *
     * @var array
     */
    private static array $loaded = [];

    /**
     * Bypassable facades.
     *
     * @var array
     */
    private static array $bypassable = [];

    /**
     * The framework instance.
     *
     * @var Application
     */
    private static Application $application;

    /**
     * Facade constructor.
     */
    final public function __construct() {}

    /**
     * Swap loaded instance with another one specified.
     *
     * @param object $object
     * @return static
     */
    public static function swap(object $object): static
    {
        self::$loaded[static::getFacadeAccessor()] = $object;
        return new static;
    }

    /**
     * Return the requested accessor name.
     *
     * @return string
     */
    public static function getFacadeAccessor(): string
    {
        if (!method_exists(static::class, 'getFacadeAccessorName')) {
            throw new RuntimeException("Facade does not implement a vital method [getFacadeAccessorName]");
        }

        return static::getFacadeAccessorName();
    }

    /**
     * Get the accessors name.
     *
     * @return string
     */
    public static function getFacadeAccessorName(): string
    {
        return '';
    }

    /**
     * Reset the facade instance.
     *
     * @return static
     */
    public static function reset(): static
    {
        static::deleteLoadedInstance(static::getFacadeAccessor());
        return new static;
    }

    /**
     * Delete a loaded instance from the container.
     *
     * @param string $name
     * @return void
     */
    protected static function deleteLoadedInstance(string $name): void
    {
        unset(self::$loaded[$name]);
    }

    /**
     * Return the facade class.
     *
     * @return object|null
     */
    public static function class(): ?object
    {
        return static::resolveFacadeInstance();
    }

    /**
     * Resolve and return an instantiated object of the facade.
     *
     * @return object|null
     */
    protected static function resolveFacadeInstance(): ?object
    {
        return static::loadFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Load the requested facade class.
     *
     * @param object|string $class
     * @return object|null
     */
    protected static function loadFacadeInstance(object|string $class): ?object
    {
        if (is_object($class)) {
            return $class;
        }

        if (array_key_exists($class, self::$bypassable)) {

            static::deleteBypassableInstance($class);

            if (class_exists($object = static::getFacadeClass())) {
                return self::$loaded[$class] = new $object;

            } else {
                throw new RuntimeException("The requested class could not be loaded");
            }

        }

        if (null !== $object = static::getLoadedInstance($class)) {
            return $object;
        }

        if (isset(self::$application)) {
            return self::$loaded[$class] = self::$application->{$class};
        }

        return null;
    }

    /**
     * Delete a bypassable instance from the container.
     *
     * @param string $name
     * @return void
     */
    protected static function deleteBypassableInstance(string $name): void
    {
        unset(self::$bypassable[$name]);
    }

    /**
     * Return the requested accessor class.
     *
     * @return string
     */
    public static function getFacadeClass(): string
    {
        if (!method_exists(static::class, 'getFacadeClassName')) {
            throw new RuntimeException("Facade does not implement a vital method [getFacadeClassName]");
        }

        return static::getFacadeClassName();
    }

    /**
     * Get the facades class name.
     *
     * @return string
     */
    public static function getFacadeClassName(): string
    {
        return '';
    }

    /**
     * Get a loaded instance from the container.
     *
     * @param string $name
     * @return object|null
     */
    protected static function getLoadedInstance(string $name): ?object
    {
        if (array_key_exists($name, self::$loaded)) {
            return self::$loaded[$name];
        }

        return null;
    }

    /**
     * Set facade to bypass application inheritance.
     *
     * @return object|null
     */
    public static function new(): ?object
    {
        self::$bypassable[static::getFacadeAccessor()] = true;
        return new static;
    }

    /**
     * Set the application framework.
     *
     * @param Application $application
     * @return void
     */
    public static function setFacadeApplication(Application $application): void
    {
        self::$application = $application;
    }

    /**
     * Clear all the loaded instances.
     *
     * @return void
     */
    public static function clearResolvedInstances(): void
    {
        self::$loaded = [];
    }

    /**
     * Call a facade method with "->" operator.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        return static::__callStatic($method, $args);
    }

    /**
     * Call a facade statically.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::resolveFacadeInstance();

        if (is_null($instance)) {
            throw new RuntimeException(sprintf('Cannot resolve the facade accessor [%s]', static::getFacadeAccessor()));
        }

        if ($instance instanceof Closure) {
            return $instance(...$args);
        }

        if (!method_exists($instance, $method)) {

            if (!is_callable([$instance, $method])) {
                throw new RuntimeException("Facade class [" . get_class($instance) . "] does not implement the requested method [$method]");
            }
        }

        return $instance->$method(...$args);
    }

}
