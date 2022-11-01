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

namespace Twipsi\Support\Bags;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use InvalidArgumentException;
use ReflectionParameter;

class ObjectBag
{
    /**
     * Contains reflection class.
     * 
     * @var ReflectionClass
     */
    protected ReflectionClass $class;

    /**
     * Construct our reflection class.
     * 
     * @param string $class
     * @throws InvalidArgumentException
     */
    public function __construct(string $class)
    {
        if (! class_exists($class)) {
            throw new InvalidArgumentException(sprintf("The requested class could not be found [%s]", $class));
        }

        $this->class = new ReflectionClass($class);
    }

    /**
     * Check if class is instantiable.
     * 
     * @return bool
     */
    public function isInstantiable(): bool
    {
        return $this->class->isInstantiable();
    }

    /**
     * Check if class has a constructor.
     * 
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return null !== $this->class->getConstructor();
    }

    /**
     * Return the constructor.
     * 
     * @return ReflectionMethod|null
     */
    public function getConstructor(): ?ReflectionMethod
    {
        return $this->class->getConstructor();
    }

    /**
     * Return the constructor parameters.
     * 
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->getConstructor()->getParameters();
    }

    /**
     * Return a new instance with the parameters provided.
     *
     * @param array $dependencies
     *
     * @return object
     * @throws ReflectionException
     */
    public function instantiateWithParameters(array $dependencies): object
    {
        return $this->class->newInstanceArgs($dependencies);
    }

    /**
     * Check if a class implements an interface.
     * 
     * @param string $interface
     * @return bool
     */
    public function implements(string $interface): bool
    {
        return $this->class->implementsInterface($interface);
    }

    /**
     * Check if a class extends a class.
     * 
     * @param string $class
     * @return bool
     */
    public function extends(string $class): bool
    {
        return $this->class->isSubclassOf($class);
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     * @return mixed
     * @throws ReflectionException
     */
    public function property(string $name): mixed
    {
        return $this->class->getProperty($name)->getValue();
    }

    /**
     * Check if a property exists.
     * 
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->class->hasProperty($name);
    }

    /**
     * Check if class has a method.
     * 
     * @param string $method
     * @return bool
     */
    public function has(string $method): bool 
    {
        return $this->class->hasMethod($method);
    }

    /**
     * Get a method from a class.
     *
     * @param string $method
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    public function method(string $method): ReflectionMethod
    {
        return $this->class->getMethod($method);
    }

    /**
     * Get class method parameters.
     *
     * @param string $method
     * @return array
     * @throws ReflectionException
     */
    public function methodParameters(string $method): array
    {
        return $this->method($method)->getParameters();
    }

    /**
     * Check if a property is initialized.
     *
     * @param string $name
     * @return bool
     * @throws ReflectionException
     */
    public function initialized(string $name): bool
    {
        return $this->class->getProperty($name)->isInitialized();
    }

    /**
     * If we don't have an override invoke reflectionClass methods.
     * 
     * @param string $method
     * @param array|null $args
     * @return mixed
     */
    public function __call(string $method, array $args = null): mixed
    {
        if (! method_exists($this, $method)) {
            return !$args ? $this->class->{$method}() : $this->class->{$method}(...$args);
        }

        return $this->{$method}($args);
    }
}
