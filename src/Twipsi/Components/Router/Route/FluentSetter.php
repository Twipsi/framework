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

namespace Twipsi\Components\Router\Route;

use Closure;

trait FluentSetter
{
    /**
     * Set or extend route name.
     *
     * @param string $name
     * @return $this
     */
    public function name(string $name): static
    {
        $name = ($current = $this->getName()) ? $current.'.'.$name : $name;
        $this->setName($name);

        return $this;
    }

    /**
     * Prepend a prefix to the url with no slash at the end.
     *
     * @param string $prefix
     * @return $this
     */
    public function prefix(string $prefix): static
    {
        $this->setPrefix(
            $this->getPrefix().'/'.trim($prefix, '/')
        );

        return $this;
    }

    /**
     * merge the route context.
     *
     * @param string $context
     * @return static
     */
    public function context(string $context): static
    {
        $context = ($current = $this->getContext())
            ? ($current . '/' . $context)
            : $context;

        $this->setContext($context);

        return $this;
    }

    /**
     * Set the default value for a parameter.
     *
     * @param array $defaults
     * @return $this
     */
    public function default(array $defaults): static
    {
        $this->setParameterValues(
            array_merge($this->getParameterValues(), $defaults)
        );

        return $this;
    }

    /**
     * Set a fallback closure if controller returns null.
     *
     * @param Closure $closure
     * @return $this
     */
    public function fallback(Closure $closure): static
    {
        $this->setFallback($closure);

        return $this;
    }

    /**
     * Set the route scheme to match.
     *
     * @param string $scheme
     * @return $this
     */
    public function scheme(string $scheme): static
    {
        $this->setRouteScheme($scheme);

        return $this;
    }

    /**
     * Add a custom regex to match the route uri.
     *
     * @param string $regex
     * @return $this
     */
    public function custom(string $regex) : static
    {
        $this->setCustomRegex($regex);

        return $this;
    }

    /**
     * Add regex patterns to parameters.
     *
     * @param array $regexes
     * @return $this
     */
    public function regex(array $regexes) : static
    {
        if (array_key_exists('default', $regexes)) {
            $this->setDefaultParameterRegex($regexes['default']);
            unset($regexes['default']);
        }

        $merged = array_merge($this->getParameterRegex(), $regexes);
        $this->setParameterRegex($merged);

        return $this;
    }

    /**
     * Merge conditional values for the parameters.
     *
     * @param array $conditions
     * @return $this
     */
    public function condition(array $conditions): static
    {
        $merged = array_merge($this->getParameterConditions(), $conditions);
        $this->setParameterConditions($merged);

        return $this;
    }

    /**
     * Merge exception values for the parameters.
     *
     * @param array $exceptions
     * @return $this
     */
    public function exception(array $exceptions): static
    {
        $merged = array_merge($this->getParameterExceptions(), $exceptions);
        $this->setParameterExceptions($merged);

        return $this;
    }

    /**
     * Add an optional parameter.
     *
     * @param string ...$parameters
     * @return $this
     */
    public function optional(string... $parameters): static
    {
        $merged = array_merge($this->getOptionalParameters(), $parameters);
        $this->setOptionalParameters($merged);

        return $this;
    }

    /**
     * Set or Merge route middlewares.
     *
     * @param string ...$middlewares
     * @return $this
     */
    public function middleware(string ...$middlewares) : static
    {
        $this->setMiddlewares(
            array_merge($this->getMiddlewares(), $middlewares)
        );

        return $this;
    }

    /**
     * Inject properties into the route from an array.
     * This is used to merge grouped route attributes.
     *
     * @param array $properties
     * @return void
     */
    public function mergeRoutePropertiesWith(array $properties): void
    {
        foreach ($properties as $property => $value) {

            switch ($property) {
                case 'name':
                case 'n':
                    is_null($value) ?: $this->name($value);
                    break;
                case 'namespace':
                case 'ns':
                    is_null($value) ?: !method_exists($this, 'namespace')
                        ?: $this->namespace($value);
                    break;
                case 'prefix':
                case 'p':
                    is_null($value) ?: $this->prefix($value);
                    break;
                case 'context':
                case 'ct':
                    is_null($value) ?: $this->context($value);
                    break;
                case 'scheme':
                case 's':
                    is_null($value) ?: $this->scheme($value);
                    break;
                case 'regex':
                case 'r':
                    is_null($value) ?: $this->regex($value);
                    break;
                case 'condition':
                case 'cn':
                    is_null($value) ?: $this->condition($value);
                    break;
                case 'exception':
                case 'ex':
                    is_null($value) ?: $this->exception($value);
                    break;
                case 'optional':
                case 'o':
                    is_null($value) ?: $this->optional(...$value);
                    break;
                case 'middlewares':
                case 'm':
                    is_null($value) ?: $this->middleware(...$value);
                    break;

            }
        }
    }
}
