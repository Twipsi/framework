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
use InvalidArgumentException;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;
use Twipsi\Support\Str;

abstract class Route
{
    use FluentSetter, Serializable;

    /**
     * The name of the route.
     *
     * @var string
     */
    protected string $name;

    /**
     * The uri of the route.
     *
     * @var string
     */
    protected string $uri;

    /**
     * The route callback to run.
     *
     * @var mixed
     */
    protected mixed $callback;

    /**
     * The fallback to run.
     *
     * @var mixed
     */
    protected mixed $fallback;

    /**
     * The url prefix.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * The route context.
     *
     * @var string
     */
    protected string $context;

    /**
     * The custom regex to use.
     *
     * @var string
     */
    protected string $customRegex;

    /**
     * The url scheme to match.
     *
     * @var string
     */
    protected string $scheme;

    /**
     * Default parameter regex to fallback.
     *
     * @var string
     */
    protected string $defaultParameterRegex;

    /**
     * Allowed request methods for the route.
     *
     * @var array
     */
    protected array $allowedRequestMethods = [];

    /**
     * Parameter conditions to match.
     *
     * @var array
     */
    protected array $parameterConditions = [];

    /**
     * Parameter exceptions to match.
     * @var array
     */
    protected array $parameterExceptions = [];

    /**
     * Parameter regexes.
     *
     * @var array
     */
    protected array $parameterRegex = [];

    /**
     * Middlewares to run on the route.
     *
     * @var array
     */
    protected array $middlewares = [];

    /**
     * Optional parameters.
     *
     * @var array
     */
    protected array $optionalParameters = [];

    /**
     * Processed parameter values.
     *
     * @var array
     */
    protected array $parameterValues = [];

    /**
     * Route constructor.
     *
     * @param string $uri
     * @param mixed $callback
     * @param array $methods
     */
    public function __construct(string $uri, mixed $callback, array $methods)
    {
        // Set the compulsory route data.
        $this->setUrl($uri);
        $this->setCallback($callback);
        $this->setAllowedRequestMethods(...$methods);
    }

    /**
     * Initiate route rendering and return a valid response.
     *
     * @return mixed
     * @throws InvalidRouteException
     */
    abstract public function render(): mixed;

    /**
     * Set provided uri for the route.
     *
     * @param string $uri
     * @return void
     */
    public function setUrl(string $uri): void
    {
        // Must add '/' to the end and front
        $this->uri = $uri === "/" ? "/"
            : Str::hay(trim($uri, "/"))->wrap("/");
    }

    /**
     * Return stored route url with the prefix.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return ($this->prefix ?? '') . $this->uri;
    }

    /**
     * Run the fallback closure.
     *
     * @return mixed
     */
    public function runFallback(): mixed
    {
        return $this->fallback instanceof Closure
            ? $this->fallback->call($this) : null;
    }

    /**
     * Set a fallback closure if controller returns null.
     *
     * @param mixed $closure
     * @return void
     */
    public function setFallback(mixed $closure): void
    {
        $this->fallback = $closure;
    }

    /**
     * Check if we have a fallback closure.
     *
     * @return bool
     */
    public function hasFallback(): bool
    {
        return isset($this->fallback);
    }

    /**
     * Set provided callback for the route.
     *
     * @param mixed $callback
     * @return void
     */
    public function setCallback(mixed $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * Return stored route callback.
     *
     * @return mixed
     */
    public function getCallback(): mixed
    {
        return $this->callback ?? null;
    }

    /**
     * Set provided name for the route.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return stored route name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * Set provided prefix for the route.
     *
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Return stored route prefix.
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix ?? null;
    }

    /**
     * Set provided context for the route.
     *
     * @param string $context
     * @return void
     */
    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    /**
     * Return stored route context.
     *
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context ?? null;
    }

    /**
     * Set a custom regex pattern to match against uri.
     *
     * @param string $regex
     * @return void
     */
    public function setCustomRegex(string $regex): void
    {
        $this->customRegex = $regex;
    }

    /**
     * Get the custom regex pattern.
     *
     * @return string|null
     */
    public function getCustomRegex(): ?string
    {
        return $this->customRegex ?? null;
    }

    /**
     * Set the route scheme.
     *
     * @param string $scheme
     * @return void
     */
    public function setRouteScheme(string $scheme): void
    {
        $this->scheme = $scheme;
    }

    /**
     * get the route scheme.
     *
     * @return string|null
     */
    public function getRouteScheme(): ?string
    {
        return $this->scheme ?? null;
    }

    /**
     * Get the value of a parameter.
     * 
     * @param string $key
     * @return mixed
     */
    public function value(string $key): mixed
    {
        return !isset($this->parameterValues[$key]) ?: $this->parameterValues[$key];
    }

    /**
     * Set a set of parameters and values.
     *
     * @param array $parameters
     * @return void
     */
    public function setParameterValues(array $parameters): void
    {
        $this->parameterValues = $parameters;
    }

    /**
     * Return saved parameter values.
     *
     * @return array
     */
    public function getParameterValues(): array
    {
        return $this->parameterValues;
    }

    /**
     * Set provided request methods for the route.
     *
     * @param string ...$methods
     * @return void
     */
    public function setAllowedRequestMethods(string ...$methods): void
    {
        $this->allowedRequestMethods = array_merge(
            $this->allowedRequestMethods, array_fill_keys($methods, "allowed")
        );
    }

    /**
     * Return stored route request methods.
     *
     * @return array
     */
    public function getAllowedRequestMethods(): array
    {
        return $this->allowedRequestMethods;
    }

    /**
     * Set default condition regex for the route.
     * used on lowest level parameter unless unique one is set.
     *
     * @param array $regexes
     * @return void
     */
    public function setParameterRegex(array $regexes): void
    {
        $this->parameterRegex = $regexes;
    }

    /**
     * Return stored route default condition regex.
     *
     * @return array
     */
    public function getParameterRegex(): array
    {
        return $this->parameterRegex;
    }

    /**
     * Set default regex for the route.
     *
     * @param string $regex
     * @return void
     */
    public function setDefaultParameterRegex(string $regex): void
    {
        $this->defaultParameterRegex = $regex;
    }

    /**
     * Return stored route default regex.
     *
     * @return string|null
     */
    public function getDefaultParameterRegex(): ?string
    {
        return $this->defaultParameterRegex ?? null;
    }

    /**
     * Set condition regex for the parameters.
     *
     * @param array $conditions
     * @return void
     */
    public function setParameterConditions(array $conditions): void
    {
        if(! empty($this->parameterExceptions)) {
            foreach(array_keys($conditions) as $parameter) {

                if(! isset($this->parameterExceptions[$parameter])) {
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    "You have already set a condition for route parameter '%s'.", $parameter
                ));
            }
        }

        $this->parameterConditions = $conditions;
    }

    /**
     * Return stored parameter condition regexes.
     *
     * @return array
     */
    public function getParameterConditions(): array
    {
        return $this->parameterConditions;
    }

    /**
     * Set exception values for the parameters.
     *
     * @param array $exceptions
     * @return void
     */
    public function setParameterExceptions(array $exceptions): void
    {
        if(! empty($this->parameterConditions)) {
            foreach(array_keys($exceptions) as $parameter) {

                if(! isset($this->parameterConditions[$parameter])) {
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    "You have already set an exception for route parameter '%s'.", $parameter
                ));
            }
        }

        $this->parameterExceptions = $exceptions;
    }

    /**
     * Return stored parameter condition values.
     *
     * @return array
     */
    public function getParameterExceptions(): array
    {
        return $this->parameterExceptions;
    }

    /**
     * Set optional parameters.
     *
     * @param array $parameters
     * @return void
     */
    public function setOptionalParameters(array $parameters): void
    {
        $this->optionalParameters = $parameters;
    }

    /**
     * Get the optional parameters.
     *
     * @return array
     */
    public function getOptionalParameters(): array
    {
        return $this->optionalParameters;
    }

    /**
     * Set middlewares for the route.
     *
     * @param array $middlewares
     * @return void
     */
    public function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Return stored middlewares of the route.
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
