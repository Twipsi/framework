<?php

namespace Twipsi\Components\Router\Matcher;

use Twipsi\Components\Router\Route\Route;
use Twipsi\Support\Str;

final class RegexMatcher
{
    /**
     * Match the request uri against the route uri.
     *
     * @param Route $route
     * @param string $request
     * @return bool|array
     */
    public function match(Route $route, string $request): bool|array
    {
        // If we have a custom pattern set for the route use it to match
        // instead of the built-in one.
        if (($regex = $route->getCustomRegex() )
                && !preg_match($regex, $this->formatRequestUri($request), $matches)) {

            return false;
        }

        return isset($matches) ? $this->filterMatchForValues($matches) : true;
    }

    /**
     * Format the request uri to make sure it matches the pattern.
     *
     * @param string $request
     * @return string
     */
    protected function formatRequestUri(string $request): string
    {
        return Str::hay(trim($request, "/"))->wrap("/");
    }

    /**
     * Get the parameter => value pairs from the pregmatch.
     *
     * @param array $matches
     * @return array
     */
    protected function filterMatchForValues(array $matches): array
    {
        return array_filter($matches, function ($k) {
            return is_string($k);
        },
            ARRAY_FILTER_USE_KEY
        );
    }
}