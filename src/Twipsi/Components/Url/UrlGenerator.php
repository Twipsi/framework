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

namespace Twipsi\Components\Url;

use Closure;
use InvalidArgumentException;
use Twipsi\Components\Http\Exceptions\MalformedUrlException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;
use Twipsi\Components\Router\Matcher\UriMatcher;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Components\Router\RouteBag;
use Twipsi\Components\Session\SessionItem;

final class UrlGenerator
{
    /**
     * The request object.
     *
     * @var HttpRequest
     */
    protected HttpRequest $request;

    /**
     * The session item.
     *
     * @var Closure
     */
    protected Closure $session;

    /**
     * System key resolver.
     *
     * @var Closure
     */
    protected Closure $keyResolver;

    /**
     * The route collection.
     *
     * @var RouteBag
     */
    protected RouteBag $routes;

    /**
     * Url generator constructor.
     *
     * @param HttpRequest $request
     * @param RouteBag $routes
     */
    public function __construct(HttpRequest $request, RouteBag $routes)
    {
        $this->request = $request;
        $this->routes = $routes;
    }

    /**
     * Find a route based on a name and generate url.
     *
     * @param string $name
     * @param array $parameters
     * @return string|null
     */
    public function route(string $name, array $parameters = []): ?string
    {
        if ($route = $this->routes->byName($name)) {
            return $this->fromRoute($route, $parameters);
        }

        throw new RouteNotFoundException(sprintf("Route with name [%s] not found", $name));
    }

    /**
     * Convert router uri to a compiled url based on parameters.
     *
     * @param Route $route
     * @param array $parameters
     * @return string|null
     */
    public function fromRoute(Route $route, array $parameters = []): ?string
    {
        $uri = (new UrlBuilder)->build($route->getUrl(), $parameters, $route->getOptionalParameters());

        if (false !== (new UriMatcher())->match($route, $uri)) {
            return $this->compile($uri);
        }

        throw new InvalidArgumentException(sprintf(
            "Route [%s] could not be built because of missing parameters", $route->getUrl())
            );
    }

    /**
     * Get the url based on a controller action
     *
     * @param string|array $action
     * @param array $parameters
     * @return string|null
     */
    public function action(string|array $action, array $parameters = []): ?string
    {
        if ($route = $this->routes->byAction($action)) {
            return $this->fromRoute($route, $parameters);
        }

        $action = is_array($action) ? implode('@', $action): $action;

        throw new RouteNotFoundException(sprintf("Route with controller action [%s] not found", $action));
    }

    /**
     * Generate a safe signed url with signature or/and expiry.
     *
     * @param string $name
     * @param array $parameters
     * @param int|string|null $expires
     * @return string
     */
    public function signed(string $name, array $parameters = [], int|string $expires = null): string
    {
        return (new SignedUrl($this))->sign($name, $parameters, $expires);
    }

    /**
     * Generate a secure (https) url.
     *
     * @param string $url
     * @return string
     */
    public function secure(string $url): string
    {
        return $this->compile($url, true);
    }

    /**
     * Get the current URL from the request.
     *
     * @return string
     */
    public function current(): string
    {
        return $this->compile($this->request->url()->getPath());
    }

    /**
     * Get the previous url the request or the session.
     *
     * @param string $fallback
     * @param bool|null $secure
     * @return string
     */
    public function previous(string $fallback = '/', ?bool $secure = null): string
    {
        if ($referer = $this->request->getReferer()) {
            return $this->compile($referer);
        }

        return $this->compile(
            $this->getSession()->get('_previous.url', $fallback), $secure
        );
    }

    /**
     * Get the intended url from the session while also removing it.
     *
     * @param string $fallback
     * @return string
     */
    public function intended(string $fallback = '/'): string
    {
        return $this->compile(
            $this->getSession()->pull('_intended.url', $fallback)
        );
    }

    /**
     * Compile the path to a valid url.
     *
     * @param string $url
     * @param bool|null $secure
     * @return string|null
     */
    public function compile(string $url, ?bool $secure = null): ?string
    {
        // If it's a valid url already then return it.
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Build an url item and generate a valid url from the path.
        try{
            $url = (new UrlItem($url))->setScheme($this->scheme($secure));

        } catch (MalformedUrlException) {
            return null;
        }

        if (is_null($url->getHost())) {
            $url->setHost($this->request->url()->getHost());
        }

        return $url->getAbsoluteUrl();
    }

    /**
     * Get the possible url scheme.
     *
     * @param bool|null $secure
     * @return string|null
     */
    protected function scheme(?bool $secure = null): ?string
    {
        if (! is_null($secure)) {
            return $secure ? 'https' : 'http';
        }

        return $this->request->url()->getScheme();
    }

    /**
     * Set the session loader.
     *
     * @param Closure $callback
     * @return void
     */
    public function setSessionLoader(Closure $callback): void
    {
        $this->session = $callback;
    }

    /**
     * Load and get the session.
     *
     * @return SessionItem
     */
    public function getSession(): SessionItem
    {
        return call_user_func($this->session);
    }

    /**
     * Set the key resolver callback.
     *
     * @param Closure $callback
     * @return void
     */
    public function setSystemKey(Closure $callback): void
    {
        $this->keyResolver = $callback;
    }

    /**
     * Get the system key.
     *
     * @return string
     */
    public function getSystemKey(): string
    {
        return call_user_func($this->keyResolver);
    }
}
