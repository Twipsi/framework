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

namespace Twipsi\Foundation\Middleware;

use Closure;
use Twipsi\Components\Http\Exceptions\HttpResponseException;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Http\Response\Response;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Middleware\Exceptions\InvalidMiddlewareException;
use Twipsi\Support\Bags\SimpleBag as Container;

class MiddlewareResolver
{
    /**
     * Application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Middleware response hooks.
     *
     * @var Container
     */
    protected Container $hooks;

    /**
     * Construct Resolver.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->hooks = new Container();
    }

    /**
     * Resolve middleware collection.
     *
     * @param MiddlewareCollector $collection
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    public function resolve(MiddlewareCollector $collection): void
    {
        if ($collection->has('general')) {
            $this->resolveGeneralMiddlewares($collection->get('general'));
        }

        if ($collection->has('group')) {
            $this->resolveGroupMiddlewares($collection->get('group'));
        }

        if ($collection->has('single')) {
            $this->resolveSingleMiddlewares($collection->get('single'));
        }

        if ($collection->has('custom')) {
            $this->resolveCustomMiddlewares($collection->get('custom'));
        }
    }

    /**
     * Resolve general middlewares.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected function resolveGeneralMiddlewares(array $middlewares): void
    {
        $this->handle($middlewares);
    }

    /**
     * Resolve group middlewares.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected function resolveGroupMiddlewares(array $middlewares): void
    {
        $this->handle($middlewares);
    }

    /**
     * Resolve single middlewares.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected function resolveSingleMiddlewares(array $middlewares): void
    {
        $this->handle($middlewares);
    }

    /**
     * Resolve custom middleware.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected function resolveCustomMiddlewares(array $middlewares): void
    {
        $this->handle($middlewares);
    }

    /**
     * Resolve middlewares and handle result.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException|InvalidMiddlewareException
     */
    protected function handle(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {

            // If the middleware returns a closure add it the hooks for later processing.
            if (($resolved = $this->dispatch($middleware)) instanceof Closure) {
                $this->hooks->set(is_array($middleware) ? $middleware[0] : $middleware, $resolved);
            }

            // If we are returning a response, then exit the application with an exception.
            if ($resolved instanceof Response) {
                throw new HttpResponseException($resolved);
            }
        }
    }

    /**
     * Dispatch requested middleware
     *
     * @param string|array $middleware
     * @return ResponseInterface|Closure|bool
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected function dispatch(string|array $middleware): ResponseInterface|Closure|bool
    {
        // If it is a single or custom check for custom arguments.
        if (is_array($middleware)) {
            [$middleware, $args] = $middleware;
        }

        // Make class through the application.
        $middleware = $this->app->make($middleware);

        // If it's not a valid middleware interface exit.
        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidMiddlewareException(
                sprintf("Middleware: [%s] does not implement 'MiddlewareInterface'", get_class($middleware))
            );
        }

        if (isset($args) && is_array($args)) {
            return $middleware->resolve($this->app->get('request'), ...$args);
        }

        return $middleware->resolve($this->app->get('request'));
    }

    /**
     * Return the hooks.
     *
     * @return Container
     */
    public function getHooks(): Container
    {
        return $this->hooks;
    }
}
