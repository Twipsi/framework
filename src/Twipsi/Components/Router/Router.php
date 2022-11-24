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
use Twipsi\Components\Events\EventHandler as Dispatcher;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Router\Events\RouteMatchedEvent;
use Twipsi\Components\Router\Events\RouteNotFoundEvent;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;
use Twipsi\Components\Router\Matcher\RouteMatcher;
use Twipsi\Components\Router\Route\RedirectRoute;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Components\Router\Route\ViewRoute;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;

final class Router
{
    /**
     * Loaded route collection
     *
     * @var RouteBag
     */
    protected RouteBag $loadedRoutes;

    /**
     * Processed route collection
     *
     * @var RouteBag
     */
    protected RouteBag $processedRoutes;

    /**
     * RouteFactory object
     *
     * @var RouteFactory
     */
    protected RouteFactory $factory;

    /**
     * HttpRequest object
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Event dispatcher
     *
     * @var Dispatcher|null
     */
    protected Dispatcher|null $dispatcher;

    /**
     * Application object
     *
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Router constructor.
     *
     * @param Request $request
     * @param RouteFactory $factory
     * @param Dispatcher|null $dispatcher
     * @param Application|null $app
     */
    public function __construct(Request $request, RouteFactory $factory, Dispatcher $dispatcher = null, Application $app = null)
    {
        $this->app = $app;
        $this->request = $request;
        $this->factory = $factory;
        $this->dispatcher = $dispatcher;

        $this->loadedRoutes = new RouteBag;
        $this->processedRoutes = new RouteBag;
    }

    /**
     * Find any matching routes for the request.
     *
     * @param Request $request
     * @return Route|null
     * @throws ApplicationManagerException
     * @throws ReflectionException|NotSupportedException
     */
    public function match(Request $request): ?Route
    {
        // Process route defaults and parameters otherwise exit.
        if (!$this->process($this->getRoutes())) {
            return null;
        }

        // Attempt to match the provided request url to a defined route.
        // Otherwise, throw a 404 as we couldn't match request to any route.
        if (!$route = RouteMatcher::match($request, $this->processedRoutes)) {

            // Dispatch built in Route not found event if we have a dispatcher attached.
            is_null($this->dispatcher)
                ?: $this->dispatcher->dispatch(RouteNotFoundEvent::class, $request);

            throw new RouteNotFoundException(
                sprintf("Route [%s] not found", $request->url()->getPath())
            );
        }

        // If we are running with an IOC container, register the route.
        if(!is_null($this->app)) {
            $this->app->instance(Route::class, $route);
        }

        // Dispatch built in Route not found event if we have a dispatcher attached.
        is_null($this->dispatcher)
            ?: $this->dispatcher->dispatch(
                RouteMatchedEvent::class, $route, $request->url()->getPath()
            );

        return $route;
    }

    /**
     * Process routes and uri before routing.
     *
     * @param RouteBag $routes
     * @return bool
     */
    protected function process(RouteBag $routes): bool
    {
        // If there is no valid request rewrite or url specified
        // oir any routes set... exit.
        if (! $this->request->url->original() || $routes->empty()) {
            return false;
        }

        // Filter invalid routes just in case.
        $routes->filter(
            fn($route) => !empty($route->getUrl())
                && ($route instanceof ViewRoute || $route instanceof RedirectRoute)
                || !empty($route->getCallback())
        );

        $this->processedRoutes = $routes;
        return true;
    }

    /**
     * Render the route.
     *
     * @param Route $route
     * @return mixed
     */
    public function render(Route $route): mixed
    {
        // Add the route to the loaded stack.
        $this->addLoadedRoute($route);

        // If the route throws an exception, and we have a
        // fallback set, then call the fallback closure.
        try {
            return $route->render();

        } catch (InvalidRouteException $e) {
            return $route->hasFallback() ? $route->runFallback() : throw $e;
        }
    }

    /**
     * Add a route to our loaded stack.
     *
     * @param Route $route
     * @return void
     */
    public function addLoadedRoute(Route $route): void
    {
        $this->loadedRoutes->set($route->getName(), $route);
    }

    /**
     * Return all the registered routes.
     *
     * @return RouteBag
     */
    public function getRoutes(): RouteBag
    {
        return $this->factory->routes();
    }

    /**
     * Return all the loaded routes.
     *
     * @return RouteBag
     */
    public function getLoadedRoutes(): RouteBag
    {
        return $this->loadedRoutes;
    }

    /**
     * Set the request object on the router.
     *
     * @param Request $request
     * @return $this
     */
    public function setRequest(HttpRequest $request): Router
    {
        $this->request = $request;

        return $this;
    }
}
