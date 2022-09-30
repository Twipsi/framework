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

namespace Twipsi\Components\Router\Matcher;

use Twipsi\Components\Http\Exceptions\NotSupportedException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Components\Router\RouteBag;

class RouteMatcher
{
    /**
     * Match request url route with routes and check validity.
     *
     * @param HttpRequest $request
     * @param RouteBag $routes
     * @return Route|null
     * @throws NotSupportedException
     */
    public static function match(HttpRequest $request, RouteBag $routes): ?Route
    {
        if ($route = static::processRoutes($request, $routes)) {
            return $route;
        }

        throw new RouteNotFoundException(
            sprintf('Route (%s) with request method [%s] is not allowed.',
                $request->url()->getPath(),
                $request->getMethod()
            ));
    }

    /**
     * Process the routes.
     *
     * @param HttpRequest $request
     * @param RouteBag $routes
     * @return Route|null
     * @throws NotSupportedException
     */
    protected static function processRoutes(HttpRequest $request, RouteBag $routes): ?Route
    {
        //Get the request uri.
        $uri = $request->url()->getPath();

        foreach ($routes as $route) {

            // If we have a custom regex matcher, and it fails continue;
            if(false === ($response = (new RegexMatcher)->match($route, $uri))){
                continue;
            }

            // If we had a custom regex, and it matched, load the route.
            if(is_array($response) && !empty($response)) {
                return static::loadMatchedRoute($route, $response);
            }

            if(false !== ($response = (new UriMatcher)->match($route, $uri))) {

                if((new MethodMatcher)->match($route, $request)
                    && (new SchemeMatcher)->match($route, $request)
                    && (new ExceptionMatcher)->match($route, $response)
                    && (new ConditionMatcher)->match($route, $response)) {

                    return static::loadMatchedRoute($route, $response);
                }
            }
        }

        return null;
    }

    /**
     * Save the values and return the route.
     *
     * @param Route $route
     * @param array $values
     * @return Route
     */
    protected static function loadMatchedRoute(Route $route, array $values): Route
    {
        // Set the values on the route.
        $route->default($values);

        return $route;
    }
}
