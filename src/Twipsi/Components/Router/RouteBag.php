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

use Twipsi\Components\Router\Route\ControllerRoute;
use Twipsi\Components\Router\Route\LoadableRoute;
use Twipsi\Components\Router\Route\RedirectRoute;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Foundation\Application\Application;
use Twipsi\Support\Bags\ArrayBag;
use Twipsi\Support\Hasher;
use Twipsi\Support\Str;

final class RouteBag extends ArrayBag
{
    /**
     * Application instance.
     *
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Construct the route bag.
     *
     * @param Application|null $app
     */
    public function __construct(Application $app = null)
    {
        $this->app = $app;
        parent::__construct();
    }

    /**
     * Add a route to the stack with a name if possible.
     *
     * @param Route $route
     * @param string|null $name
     * @return void
     */
    public function addRoute(Route $route, string $name = null): void
    {
        // If we don't have a name generate an id.
        if(is_null($name)) {
            $route->name($name = Hasher::hashFast(get_class($route)));
        }

        // Register the named route.
        parent::set($name, $route);
    }

    /**
     * Find a named route by name.
     *
     * @param string $name
     * @return Route|null
     */
    public function byName(string $name): ?Route
    {
        return $this->get($name);
    }

    /**
     * Find a named route by action providing the controller and method.
     * "Controller@method" || [Controller, method]
     *
     * @param string|array $action
     * @return Route|null
     */
    public function byAction(string|array $action): ?Route
    {
        // Return null if we cant format the string.
        if (! $action = $this->formatControllerAction($action)) {
            return null;
        }

        foreach ($this->all() as $route) {

            // Get the callback
            if ($route instanceof ControllerRoute
                && is_array($callback = $route->getCallback())) {

                // Using resemblance in case the namespace was
                // not provided for the controller.
                if (Str::hay($action)->resembles(
                    implode('@', $callback))) {

                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Format the controller method to "Controller@method" format.
     *
     * @param string|array $action
     * @return string|null
     */
    protected function formatControllerAction(string|array $action): ?string
    {
        if(is_array($action) && isset($action[1])) {
            return implode('@', $action);
        }

        return is_string($action)
            && Str::hay($action)->has('@') ? $action : null;
    }

    /**
     * Pack the routes for storage.
     *
     * @return array
     */
    public function packRoutes(): array
    {
        // Iterate through the routes an
        foreach ($this->all() as $name => $route) {
            $collection[$name] = serialize($route);
        }

        return $collection ?? [];
    }

    /**
     * Unpack the collection and save them to the route bag.
     *
     * @param array $collection
     * @return void
     */
    public function unpackRoutes(array $collection): void
    {
        foreach ($collection as $name => $route) {

            $route = unserialize($route);

            if($route instanceof RedirectRoute) {
                !$this->app ?: $route->setGenerator($this->app->get('url'));
            } elseif ($route instanceof LoadableRoute) {
                !$this->app ?: $route->setApp($this->app);
            } elseif ($route instanceof ControllerRoute) {
                $route->setFactory(new ControllerFactory($this->app));
            }

            $this->set($name, $route);
        }
    }
}
