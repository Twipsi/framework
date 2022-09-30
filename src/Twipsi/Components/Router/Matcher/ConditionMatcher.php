<?php

namespace Twipsi\Components\Router\Matcher;

use Twipsi\Components\Router\Route\Route;

final class ConditionMatcher
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
        $conditions = $route->getParameterConditions();

        // We will loop through all the matched parameters and
        // check if we have any condition value set for the parameter.
        $filtered = array_filter($parameters, function ($parameter, $value) use ($conditions){
            return $this->isParameterValueCondition($parameter, $value, $conditions);
        },
            ARRAY_FILTER_USE_BOTH
        );

        return empty(array_diff($parameters, $filtered));
    }

    /**
     * Check if a parameter value is a condition.
     *
     * @param string $parameter
     * @param mixed $value
     * @param array $conditions
     * @return bool
     */
    protected function isParameterValueCondition(string $parameter, mixed $value, array $conditions): bool
    {
        if(!isset($conditions[$parameter])) {
            return true;
        }

        return is_array($values = $conditions[$parameter])
            ? in_array($value, $values)
            : $value === $values;
    }
}