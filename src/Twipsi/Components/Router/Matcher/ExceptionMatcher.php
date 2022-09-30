<?php

namespace Twipsi\Components\Router\Matcher;

use Twipsi\Components\Router\Route\Route;

final class ExceptionMatcher
{
    /**
     * Match the request uri against the route uri.
     *
     * @param Route $route
     * @param array $parameters
     * @return bool
     */
    public function match(Route $route, array $parameters): bool
    {
        $exceptions = $route->getParameterExceptions();

        // We will loop through all the matched parameters and
        // check if we have any exception value set for the parameter.
        $filtered = array_filter($parameters, function ($parameter, $value) use ($exceptions){
            return !$this->isParameterValueException($parameter, $value, $exceptions);
        },
            ARRAY_FILTER_USE_BOTH
        );

        return empty(array_diff($parameters, $filtered));
    }

    /**
     * Check if a parameter value is an exception.
     *
     * @param string $parameter
     * @param mixed $value
     * @param array $exceptions
     * @return bool
     */
    protected function isParameterValueException(string $parameter, mixed $value, array $exceptions): bool
    {
        if(!isset($exceptions[$parameter])) {
            return false;
        }

        return is_array($values = $exceptions[$parameter])
            ? in_array($value, $values)
            : $value === $values;
    }
}