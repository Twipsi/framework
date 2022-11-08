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

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Twipsi\Foundation\Exceptions\DependencyResolverException;
use Twipsi\Support\Bags\SimpleBag as Container;

class DependencyResolver
{
    /**
     * Custom parameter container.
     */
    protected Container $parameters;

    /**
     * Dependency resolver constructor.
     */
    public function __construct(protected IOCManager $app, array $parameters = [])
    {
        $this->parameters = new Container();

        // Incase of object add the class name as keys.
        foreach ($parameters as $key => $param) {

            // If its an object and we dont have a name for it.
            if (is_object($param) && is_int($key)) {
                $key = get_class($param);
            }

            $this->parameters->set((string)$key, $param);
        }
    }

    /**
     * Resolve trait based inheritence.
     */
    public function resolveApplicables(array $traits): ?array
    {
        foreach ($traits as $trait) {

            // If we have the append... method then its an applicable trait.
            if ($trait instanceof ReflectionClass
                && $trait->hasMethod($needle = 'append' . $trait->getShortName())) {

                // Extract the required parameters and attempt to resolve them
                // through the application interface resolver.
                $appendables = $this->resolve($trait->getMethod($needle)->getParameters());

                // Build a closure so we can run them while building.
                $closures[] = function ($object) use ($appendables, $needle) {
                    return $object->{$needle}(...$appendables);
                };
            }
        }

        return $closures ?? null;
    }


    /**
     * @param array $dependencies
     * @return array|null
     * @throws DependencyResolverException
     */
    public function resolve(array $dependencies): ?array
    {
        foreach ($dependencies as $dependency) {

            // If this parameter dependency has a custom provided value, we will
            // set the custom value for the parameter. ELse attempt to resolve the
            // parameter from reflection parameter defaults or type hints.
            if ($this->hasParameterOverride($dependency)) {

                $values[] = $this->getParameterOverride($dependency);
                continue;
            }

            // If the dependency type is a class, we can resolve it from our
            // application instance collection. Else throw an error since we
            // can not resolve unknown values.
            if ($dependency->getType() instanceof ReflectionNamedType
                && class_exists((string)$dependency->getType())) {
                $values[] = $this->handleClassParameter($dependency);
            } else {
                $values[] = $this->handleNormalParameter($dependency);
            }
        }

        return $values ?? null;
    }

    /**
     * Check if we have a custom value override for the dependecy parameter.
     */
    protected function hasParameterOverride(ReflectionParameter $parameter): bool
    {
        // If it's a class attempt to find the class name.
        if ($parameter->getType() instanceof ReflectionNamedType
            && !$parameter->getType()->isBuiltin()) {

            foreach($this->parameters as $name => $object) {
                if($name === (string)$parameter->getType()
                    || is_a($object, (string)$parameter->getType())) {

                    return true;
                }
            }

            return false;
        }

        // In case we have a named array.
        return $this->parameters->has($parameter->getName());
    }

    /**
     * Get the override value of the dependecy parameter.
     */
    protected function getParameterOverride(ReflectionParameter $parameter): mixed
    {
        // If its a class attempt to find the class name.
        if ($parameter->getType() instanceof ReflectionNamedType
            && !$parameter->getType()->isBuiltin()) {

            foreach($this->parameters as $name => $object) {
                if($name === (string)$parameter->getType()
                    || is_a($object, (string)$parameter->getType())) {

                    return $this->parameters->pull($name);
                }
            }

            return null;
        }

        return is_numeric($value = $this->parameters->pull($parameter->getName())) ? (int)$value : $value;
    }

    /**
     * Handle class type dependency parameters.
     */
    protected function handleClassParameter(ReflectionParameter $parameter): mixed
    {
        // We will attempt to build the dependency classes recursively
        // and return them as parameter values for the main class.
        try {
            return $this->app->make($parameter->getType()->getName());

        } catch (DependencyResolverException $e) {

            // If we could not resolve the dependency class parameter
            // we will check if there is a default value to use. Else
            // handle it as a normal parameter.
            return $this->handleNormalParameter($parameter);
        }
    }

    /**
     * Handle primitive type dependency parameters.
     */
    protected function handleNormalParameter(ReflectionParameter $parameter): mixed
    {
        // If we have parameters in the collection but names dont match,
        // then fill them in in order. (For route parameter hinting)
        if (!$this->parameters->empty()) {
            return is_numeric($value = $this->parameters->shift()) ? (int)$value : $value;
        }

        // If there is a default value set for the paramater use it
        // else throw an error since we cant continue.
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new DependencyResolverException(
            sprintf('Could not resolve parameter [%s] in class {%s}',
                $parameter->getName(),
                $parameter->getDeclaringClass()->getName()
            )
        );
    }

}
