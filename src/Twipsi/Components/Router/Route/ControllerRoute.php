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

namespace Twipsi\Components\Router\Route;

use Closure;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Router\ControllerFactory;
use Twipsi\Components\Router\Exceptions\ControllerFactoryException;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;
use Twipsi\Foundation\Exceptions\InvalidOperationException;
use Twipsi\Support\Str;

final class ControllerRoute extends Route
{
    /**
     * Controller factory.
     *
     * @var ControllerFactory
     */
    protected ControllerFactory $factory;

    /**
     * Discoverable namespace.
     *
     * @var string
     */
    protected string $namespace;

    /**
     * Controller route constructor.
     *
     * @param string $uri
     * @param array|string $callback
     * @param array $methods
     */
    public function __construct(string $uri, array|string $callback, array $methods)
    {
        parent::__construct($uri, $callback, $methods);
    }

    /**
     * Initiate route rendering and return a valid response.
     *
     * @return ResponseInterface
     * @throws InvalidRouteException
     */
    public function render(): ResponseInterface
    {
        if (is_null($this->callback) || is_null($class = $this->getClass())) {
            throw new InvalidRouteException("No classname or callback provided to the route.");
        }

        $namespace = $this->getNamespace();
        $controller = ($namespace && $class[0] !== '\\') ? $namespace . '\\' . $class : $class;

        try {
            return $this->factory
                ->build($controller, $this->getMethod(), $this->getParameterValues());

            // If there was an exception building the controller then throw invalid route.
        } catch (ControllerFactoryException $e) {
            throw new InvalidRouteException($e->getMessage(), $e->getPrevious());
        }
    }

    /**
     * Set provided namespace for the route.
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Return stored route namespace.
     *
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace ?? null;
    }

    /**
     * Merge namespace for the route.
     *
     * @param string $namespace
     * @return $this
     */
    public function namespace(string $namespace): ControllerRoute
    {
        $original = $this->getNamespace();

        if (is_null($original) ||  Str::hay($namespace)->first('\\')) {
            $this->setNamespace($namespace);
            return $this;
        }

        $this->setNamespace($original.'\\'.$namespace);

        return $this;
    }

    /**
     * Get called class in callback.
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        $callback = $this->getCallback();

        // If callback is ['Class', 'action']
        if (is_array($callback) && isset($callback[0])) {
            return $callback[0];
        }

        // If callback is 'Classname@action'
        if (is_string($callback)
                && Str::hay($this->callback)->contains('@')
                && !is_null($class = Str::hay($callback)->before('@'))) {

            return $class;
        }

        return null;
    }

    /**
     * Get the registered action method.
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        // If callback is ['Classname', 'action']
        if (is_array($this->callback) && isset($this->callback[1])) {
            return $this->callback[1];
        }

        // If callback is 'Classname@action'
        if (is_string($this->callback)
                && Str::hay($this->callback)->contains('@')
                && !is_null($method = Str::hay($this->callback)->after('@'))) {

            return $method;
        }

        return null;
    }

    /**
     * Set controller class in callback.
     *
     * @param string $class
     * @return void
     * @throws InvalidOperationException
     */
    public function setClass(string $class): void
    {
        if ($this->getCallback() instanceof Closure) {
            throw new InvalidOperationException("A valid closure has already been provided", 400);
        }

        $this->setCallback([$class, $this->getMethod()]);
    }

    /**
     * Set action method for the route.
     *
     * @param string $method
     * @return void
     * @throws InvalidOperationException
     */
    public function setMethod(string $method): void
    {
        if ($this->getCallback() instanceof Closure) {
            throw new InvalidOperationException("A valid closure has already been provided", 400);
        }

        $this->setCallback([$this->getClass(), $method]);
    }

    /**
     * Set the controller factory.
     *
     * @param ControllerFactory $factory
     * @return $this
     */
    public function setFactory(ControllerFactory $factory): ControllerRoute
    {
        $this->factory = $factory;
        return $this;
    }
}
