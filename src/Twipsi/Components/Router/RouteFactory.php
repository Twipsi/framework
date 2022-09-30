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

use Closure;
use RuntimeException;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;
use Twipsi\Components\Router\Route\ControllerRoute;
use Twipsi\Components\Router\Route\LoadableRoute;
use Twipsi\Components\Router\Route\RedirectRoute;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Components\Router\Route\ViewRoute;
use Twipsi\Foundation\Application\Application;
use Twipsi\Support\Str;

final class RouteFactory
{
    /**
     * Application object.
     *
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Route collection.
     *
     * @var RouteBag
     */
    protected RouteBag $routes;

    /**
     * Group collection.
     *
     * @var RouteGroup
     */
    protected RouteGroup $groups;

    /**
     * Processed route collection.
     *
     * @var array
     */
    protected array $processed = [];

    /**
     * Construct route subscriber.
     *
     * @param RouteBag|null $routes
     */
    public function __construct(RouteBag $routes = null)
    {
        $this->routes = $routes ?? new RouteBag;
        $this->groups = new RouteGroup;
    }

    /**
     * Subscribe a route that accepts GET,HEAD request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function get(string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, ['GET', 'HEAD']);
    }

    /**
     * Subscribe a route that accepts POST request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function post(string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, ['POST']);
    }

    /**
     * Subscribe a route that accepts PUT request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function put(string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, ['PUT']);
    }

    /**
     * Subscribe a route that accepts PATCH request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function patch(string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, ['PATCH']);
    }

    /**
     * Subscribe a route that accepts DELETE request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function delete(string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, ['DELETE']);
    }

    /**
     * Subscribe a route that accepts OPTIONS request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function options(string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, ['OPTIONS']);
    }

    /**
     * Subscribe a route that accepts any request method.
     *
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function any(string $uri, mixed $callback): Route
    {
        $methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        return $this->subscribe($uri, $callback, $methods);
    }

    /**
     * Subscribe a route that accepts a subset of request methods.
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $callback
     * @return Route
     */
    public function match(array $methods, string $uri, mixed $callback): Route
    {
        return $this->subscribe($uri, $callback, array_map('strtoupper', $methods));
    }

    /**
     * Subscribe a route that redirects to another if matched.
     *
     * @param string $uri
     * @param string $to
     * @param int $code
     * @return Route
     */
    public function redirect(string $uri, string $to, int $code): Route
    {
        if(($route = $this->any($uri, RedirectRoute::class))
                instanceof RedirectRoute) {

            return $route->destination($to)->code($code);
        }

        throw new RuntimeException("Twipsi could not subscribe the [redirect] Route");
    }

    /**
     * Subscribe a route that returns a view.
     *
     * @param string $uri
     * @param string $view
     * @param array $parameters
     * @return Route
     */
    public function view(string $uri, string $view, array $parameters): Route
    {
        if(($route = $this->match(['GET', 'HEAD'], $uri, ViewRoute::class))
                instanceof ViewRoute) {

            return $route->view($view)->data($parameters);
        }

        throw new RuntimeException("Twipsi could not subscribe the [view] Route");
    }

    /**
     * Create and save the route to the collection.
     *
     * @param string $uri
     * @param mixed $callback
     * @param array $methods
     * @return Route
     */
    protected function subscribe(string $uri, mixed $callback, array $methods): Route
    {
        // Add it to the temporary stack.
        $this->processed[] = $route = $this->make($uri, $callback, $methods);

        return $route;
    }

    /**
     * Add a group to the stack and register its routes.
     *
     * @param array $options
     * @param Closure $routes
     * @return void
     */
    public function group(array $options, Closure $routes): void
    {
        // Update the group stack with the current options
        // merging with options higher in the stack.
        $this->groups->append($options);

        // Register all the routes contained in the group
        // closure considering parent group attributes.
        call_user_func($routes, $this);

        // After registering the current levels routes
        // remove the group from the stack.
        $this->groups->pop();
    }

    /**
     * Create the route depending on the callback type.
     *
     * @param string $uri
     * @param mixed $callback
     * @param array $methods
     * @return Route
     */
    protected function make(string $uri, mixed $callback, array $methods): Route
    {
        // Build route by type of callback.
        $route = $this->build($uri, $methods, $callback);

        // Merge group attributes into the route item if we have groups.
        if (!$this->groups->empty()) {
            $route->mergeRoutePropertiesWith($this->groups->last());
        }

        return $route;
    }

    /**
     * Build the specific route based on the callback.
     *
     * @param string $uri
     * @param array $methods
     * @param mixed $callback
     * @return Route
     */
    public function build(string $uri, array $methods, mixed $callback): Route
    {
        // If the callback is a redirection.
        if ($this->isCallbackRedirect($callback)) {
            return (new RedirectRoute($uri, $methods))
                    ->setGenerator($this->app->get('url'));
        }

        // If the callback is a view.
        if ($this->isCallbackView($callback)) {
            return new ViewRoute($uri, $methods);
        }

        // If the callback points to a controller
        if ($this->isCallbackController($callback)
            || $isAction = $this->isControllerAction($callback)) {

            // If the callback is an action without a controller class
            // attempt to find the controller in the group stack.
            if ($isAction ?? false) {
                $callback = $this->findActionController($callback);
            }

            return (new ControllerRoute($uri, $callback, $methods))
                ->setFactory(new ControllerFactory($this->app));
        }

        if (!$callback instanceof Closure) {
            throw new InvalidRouteException("You have an error in your route callback");
        }

        // Create a normal loadable route.
        return (new LoadableRoute($uri, $callback, $methods))
            ->setApp($this->app);
    }

    /**
     * Check if the callback is pointing to a redirection.
     *
     * @param mixed $callback
     * @return bool
     */
    protected function isCallbackRedirect(mixed $callback): bool
    {
        return $callback === RedirectRoute::class;
    }

    /**
     * Check if the callback is pointing to a view.
     *
     * @param mixed $callback
     * @return bool
     */
    protected function isCallbackView(mixed $callback): bool
    {
        return $callback === ViewRoute::class;
    }

    /**
     * Check if the callback is pointing to a controller.
     *
     * @param mixed $callback
     * @return bool
     */
    protected function isCallbackController(mixed $callback): bool
    {
        return (is_array($callback) && isset($callback[0]) && isset($callback[1]))
            || (is_string($callback) && Str::hay($callback)->contains('@'));
    }

    /**
     * Check if the callback is an action in a controller.
     *
     * @param mixed $callback
     * @return bool
     */
    protected function isControllerAction(mixed $callback): bool
    {
        return is_string($callback)
            && !class_exists($callback)
            && !Str::hay($callback)->contains('@');
    }

    /**
     * Find the controller of the action in a parent group.
     *
     * @param string $action
     * @return string
     */
    protected function findActionController(string $action): string
    {
        if (!$this->groups->empty() && !class_exists($action)) {

            $group = $this->groups->last();

            if (isset($group['controller'])) {
                return $group['controller'] . '@' . $action;
            }
        }

        return $action;
    }

    /**
     * Return subscribed routes.
     *
     * @return RouteBag
     */
    public function routes(): RouteBag
    {
        // Register the remaining routes in the stack.
        foreach($this->processed as $route) {
            $this->routes->addRoute($route, $route->getName());

            array_shift($this->processed);
        }

        return $this->routes;
    }

    /**
     * Replace current route bag with another.
     *
     * @param RouteBag $routes
     * @return void
     */
    public function replace(RouteBag $routes): void
    {
        $this->routes = $routes;
    }

    /**
     * Set the application.
     *
     * @param Application|null $app
     * @return RouteFactory
     */
    public function setApp(?Application $app): RouteFactory
    {
        $this->app = $app;
        return $this;
    }
}
