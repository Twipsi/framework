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
use Twipsi\Support\Bags\ArrayBag as Container;

class MiddlewareResolver
{
    /**
     * Application instance.
     *
     * @var Application
     */
    protected static Application $app;

    /**
     * Middleware response hooks.
     *
     * @var Container
     */
    protected static Container $hooks;

    /**
     * Resolve middleware collection.
     *
     * @param MiddlewareCollector $collection
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    public static function resolve(MiddlewareCollector $collection): void
    {
        if ($collection->has('general')) {
            self::resolveGeneralMiddlewares($collection->get('general'));
        }

        if ($collection->has('group')) {
            self::resolveGroupMiddlewares($collection->get('group'));
        }

        if ($collection->has('single')) {
            self::resolveSingleMiddlewares($collection->get('single'));
        }

        if ($collection->has('custom')) {
            self::resolveCustomMiddlewares($collection->get('custom'));
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
    protected static function resolveGeneralMiddlewares(array $middlewares): void
    {
        static::handle($middlewares);
    }

    /**
     * Resolve group middlewares.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected static function resolveGroupMiddlewares(array $middlewares): void
    {
        static::handle($middlewares);
    }

    /**
     * Resolve single middlewares.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected static function resolveSingleMiddlewares(array $middlewares): void
    {
        static::handle($middlewares);
    }

    /**
     * Resolve custom middleware.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException
     * @throws InvalidMiddlewareException
     */
    protected static function resolveCustomMiddlewares(array $middlewares): void
    {
        static::handle($middlewares);
    }

    /**
     * Resolve middlewares and handle result.
     *
     * @param array $middlewares
     * @return void
     * @throws ApplicationManagerException|InvalidMiddlewareException
     */
    protected static function handle(array $middlewares): void
    {
        if(! isset(static::$hooks)) {
            static::$hooks = new Container();
        }

        foreach ($middlewares as $middleware) {

            // If the middleware returns a closure add it the hooks for later processing.
            if (($resolved = static::dispatch($middleware)) instanceof Closure) {
                static::$hooks->set(is_array($middleware) ? $middleware[0] : $middleware, $resolved);
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
    protected static function dispatch(string|array $middleware): ResponseInterface|Closure|bool
    {
        // If it is a single or custom check for custom arguments.
        if (is_array($middleware)) {
            [$middleware, $args] = $middleware;
        }

        // Make class through the application.
        $middleware = self::$app->make($middleware);

        // If it's not a valid middleware interface exit.
        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidMiddlewareException(
                sprintf("Middleware: [%s] does not implement 'MiddlewareInterface'", get_class($middleware))
            );
        }

        if (isset($args) && is_array($args)) {
            return $middleware->resolve(self::$app->get('request'), ...$args);
        }

        return $middleware->resolve(self::$app->get('request'));
    }

    /**
     * Set the application object.
     *
     * @param Application $app
     * @return void
     */
    public static function setApplication(Application $app): void
    {
        static::$app = $app;
    }

    /**
     * Return the hooks.
     *
     * @return Container
     */
    public static function getHooks(): Container
    {
        return static::$hooks;
    }
}
