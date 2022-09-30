<?php

namespace Twipsi\Components\Router\Matcher;

use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Router\Route\Route;

final class SchemeMatcher
{
    /**
     * Match the request uri against the route uri.
     *
     * @param Route $route
     * @param HttpRequest $request
     * @return bool
     */
    public function match(Route $route, HttpRequest $request): bool
    {
        // Check if the received request scheme matches the route scheme.
        if (($scheme = strtolower($route->getRouteScheme() ?? ''))
                && $request->url()->getScheme() !== $scheme) {

            return false;
        }

        return true;
    }
}