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

use Twipsi\Components\Router\Route\Route;
use Twipsi\Support\Str;

final class UriMatcher
{
    /**
     * The tokens holding the variadic parameters.
     */
    protected const PARAMETER_SELECTOR = "#\\\/\{(.*?)}#s";

    /**
     * The selector to identify inline parameter regex.
     */
    protected const REGEX_SELECTOR = ":";

    /**
     * The identifier for optional parameters.
     */
    protected const OPTIONAL_SELECTOR = "?";

    /**
     * The default fallback parameter regex.
     */
    protected const DEFAULT_PARAMETER_REGEX = "[\w-]+";

    /**
     * The default uri wrapper pattern.
     */
    protected const ROUTE_URI_REGEX = '/^%s\/?$/u';

    /**
     * The default parameter regex.
     */
    protected const PARAMETER_URI_REGEX = '((\/)(?P<%1$s>%2$s))%3$s';

    /**
     * The route object.
     *
     * @var Route
     */
    protected Route $route;

    /**
     * Match the request uri against the route uri.
     *
     * @param Route $route
     * @param string $request
     * @return bool|array
     */
    public function match(Route $route, string $request): bool|array
    {
        $this->route = $route;

        // We will extract parameters and rebuild url when trying to match
        // because of performance, so we only have to do it until we find a route
        // instead of doing it on every added route and giving route ordering
        // a meaning since heavy traffic routes if added upfront then can spare us
        // tons of extraction and matching every time.

        $pattern = $this->formatUriToRegexPattern(
            $this->formatRouteUri($this->route->getUrl())
        );

        // Check if the uri matches the request url otherwise exit.
        if (! preg_match($pattern , $this->formatRequestUri($request), $matches)) {
            return false;
        }

        // return the parameter/value pairs.
        return count($matches) > 1 ? $this->filterMatchForValues($matches) : [];
    }

    /**
     * Format the uri to be pregmatchable.
     *
     * @param string $uri
     * @return string
     */
    protected function formatUriToRegexPattern(string $uri): string
    {
        // Replace all the identifiers with the corresponding pattern in the uri.
        $formatted = preg_replace_callback(self::PARAMETER_SELECTOR, function($matches) {
            return $this->replaceParameterHolderWithRegex($matches);
        }, $uri);

        return sprintf(self::ROUTE_URI_REGEX, $formatted);
    }

    /**
     * Replace the uri tokens with the valid regex pattern.
     *
     * @param array $match
     * @return string
     */
    protected function replaceParameterHolderWithRegex(array $match): string
    {
        return sprintf(self::PARAMETER_URI_REGEX,
            ...$this->parseParameterToken($match[1])
        );
    }

    /**
     * Parse the parameter token and return [parameter, regex, optional]
     *
     * @param string $parameter
     * @return array
     */
    protected function parseParameterToken(string $parameter): array
    {
        // Check if we have an optional parameter and add it to the array.
        if($this->isParameterOptional($parameter)) {
            $optional = self::OPTIONAL_SELECTOR;
            $parameter = Str::hay($parameter)->sliceStart('?');
        }

        // Attempt to find the parameter regex in order to keep hierarchy.
        // First priority is a regex pattern set straight in the uri after the ":" separator
        // followed by the regex pattern set with the condition method followed by
        // the default regex pattern set by the condition method with the "default" key
        // and lastly the default regex pattern set as a constant in the class.

        [$parameter, $regex] = $this->resolveParameterRegex($parameter);

        // Save the condition to the route.
        $this->route->regex([$parameter => $regex]);

        return [$parameter, $regex, $optional ?? ''];
    }

    /**
     * Check if a parameter is an optional parameter.
     *
     * @param string $parameter
     * @return bool
     */
    protected function isParameterOptional(string $parameter): bool
    {
        $parameter = Str::hay($parameter)->has(self::REGEX_SELECTOR)
            ? Str::hay($parameter)->before(self::REGEX_SELECTOR)
            : $parameter;

        if(in_array($parameter, $this->route->getOptionalParameters())) {
            return true;
        }

        if(Str::hay($parameter)->first(self::OPTIONAL_SELECTOR)) {

            // Add it to the route optional collection.
            $this->route->optional($parameter);
            return true;
        }

        return false;
    }

    /**
     * Resolve the parameter regex.
     *
     * @param string $parameter
     * @return array
     */
    protected function resolveParameterRegex(string $parameter): array
    {
        // If we provided the regex in the token, extract it.
        if (Str::hay($parameter)->has(self::REGEX_SELECTOR)) {
            return explode(self::REGEX_SELECTOR, $parameter);
        }

        // If we have a parameter condition set.
        if ($condition = $this->getParameterRegex($parameter)) {
            return [$parameter, $condition];
        }

        // Otherwise set the default regex.
        return [$parameter, $this->getDefaultParameterRegex()];
    }

    /**
     * Attempt to find a parameter regex.
     *
     * @param string $parameter
     * @return string|null
     */
    protected function getParameterRegex(string $parameter): ?string
    {
        $regexes = $this->route->getParameterRegex();

        return $regexes[$parameter] ?? null;
    }

    /**
     * Get the default parameter regex.
     *
     * @return string
     */
    protected function getDefaultParameterRegex(): string
    {
        return $this->route->getDefaultParameterRegex() ?? self::DEFAULT_PARAMETER_REGEX;
    }

    /**
     * Format the request uri to make sure it matches the pattern.
     *
     * @param string $request
     * @return string
     */
    protected function formatRequestUri(string $request): string
    {
        if(Str::hay($request)->has('?')) {
            $request = Str::hay($request)->before('?');
        }

        return Str::hay(trim($request, "/"))->wrap("/");
    }

    /**
     * Format the route uri to make sure it matches the pattern.
     *
     * @param string $uri
     * @return string
     */
    protected function formatRouteUri(string $uri): string
    {
        return preg_replace("/\//", "\\/", $uri);
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