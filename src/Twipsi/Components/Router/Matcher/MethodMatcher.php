<?php

namespace Twipsi\Components\Router\Matcher;

use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Router\Route\Route;

final class MethodMatcher
{
    /**
     * Match the request uri against the route uri.
     *
     * @param Route $route
     * @param HttpRequest $request
     * @return bool
     * @throws NotSupportedException
     */
    public function match(Route $route, HttpRequest $request): bool
    {
        $methods = $route->getAllowedRequestMethods();

        // Check if the received request method is allowed for the matched route.
        if (count($methods) > 0 && !isset($methods[$request->getMethod()])) {
            return false;
        }

        return true;
    }
}