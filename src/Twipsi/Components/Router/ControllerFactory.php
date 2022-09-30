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

namespace Twipsi\Components\Router;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Twipsi\Bridge\Controller;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Router\Exceptions\ControllerFactoryException;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Application\DependencyResolver;
use Twipsi\Foundation\Exceptions\DependencyResolverException;
use TypeError;
use Throwable;

final class ControllerFactory
{
    /**
     * Application instance.
     *
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Controller factory constructor
     *
     * @param Application|null $app
     */
    public function __construct(Application $app = null)
    {
        $this->app = $app;
    }

    /**
     * Build the requested controller and invoke the required method.
     *
     * @param string $controller
     * @param string $method
     * @param array $parameters
     * @return ResponseInterface
     * @throws ControllerFactoryException
     */
    public function build(string $controller, string $method, array $parameters): ResponseInterface
    {
        if (! class_exists($controller)) {

            throw new ControllerFactoryException(
                sprintf("Controller %s does not exist", $controller));
        }

        return $this->dispatch(new $controller($this->app), $method, $parameters);
    }

    /**
     * Attempt to build dependency and invoke the method.
     *
     * @param Controller $controller
     * @param string $method
     * @param array $parameters
     * @return ResponseInterface
     * @throws ControllerFactoryException
     */
    public function dispatch(Controller $controller, string $method, array $parameters): ResponseInterface
    {
        try {
            // Attempt to resolve the dependency parameters
            // and call the class and return the response.
            return $this->callController($controller, $method,
                $this->resolveMethodDependencies($controller, $method, $parameters)
            );

        } catch (DependencyResolverException|ReflectionException $e ) {

            $message = !$e->getPrevious() ? '' : ' | '.$e->getMessage();

            throw new ControllerFactoryException(
                sprintf("Method '%s' in controller %s could not be resolved. $message",
                    $method, get_class($controller)), $e->getPrevious() ?? $e
            );
        }
    }

    /**
     * Resolve the method parameters from the IoC container.
     *
     * @param Controller $controller
     * @param string $method
     * @param array $parameters
     * @return array
     * @throws DependencyResolverException
     * @throws ReflectionException
     */
    protected function resolveMethodDependencies(Controller $controller, string $method, array $parameters): array
    {
        // Extract all the dependency parameters from the controller method.
        $dependencies = (new ReflectionMethod($controller, $method))
                ->getParameters();

        // If we don't have an IOC container then just try to call the class.
        if(is_null($this->app)) {
            return $this->resolveParametersWithoutDI($parameters, $dependencies);
        }

        // Create the class through the DI resolver.
        return (new DependencyResolver($this->app, $parameters))->resolve($dependencies);
    }

    /**
     * Call the controller method and return the response.
     *
     * @param Controller $controller
     * @param string $method
     * @param array $dependencies
     * @return ResponseInterface
     */
    protected function callController(Controller $controller, string $method, array $dependencies): ResponseInterface
    {
        // Invoke the controller method with resolved parameters.
        return !empty($dependencies)
            ? $controller->{$method}(...array_values($dependencies))
            : $controller->{$method}();
    }

    /**
     * Resolve any class type parameters if possible.
     *
     * @param array $parameters
     * @param array $dependencies
     * @return array
     */
    protected function resolveParametersWithoutDI(array $parameters, array $dependencies): array
    {
        foreach($dependencies as $dependency) {
            //If parameter is a class type.
            if($class = $this->getParameterClass($dependency)) {

                // If we don't have a match or any parameters break.
                if(!($parameters[0] instanceof $class) || empty($parameters)) {
                    break;
                }
            }

            $resolved[] = array_shift($parameters);
        }

        return $resolved ?? [];
    }

    /**
     * Get the class name of a parameter if possible.
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    protected function getParameterClass(ReflectionParameter $parameter): ?string
    {
        // If it's a class attempt to find the class name.
        return (($type = $parameter->getType()) instanceof ReflectionNamedType)
            && !$parameter->getType()->isBuiltin() ? $type->getName() : null;
    }
}
