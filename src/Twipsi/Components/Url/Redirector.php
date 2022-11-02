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

use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\Response\RedirectResponse;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;


final class Redirector
{
    /**
     * Http request.
     *
     * @var HttpRequest
     */
    protected HttpRequest $request;

    /**
     * Url Generator object.
     *
     * @var UrlGenerator
     */
    protected UrlGenerator $generator;

    /**
     * Redirector constructor.
     *
     * @param HttpRequest $request
     * @param UrlGenerator $generator
     */
    public function __construct(HttpRequest $request, UrlGenerator $generator)
    {
        $this->request = $request;
        $this->generator = $generator;
    }

    /**
     * Redirect to the home route.
     *
     * @param int $code
     * @return RedirectResponse
     */
    public function home(int $code = 302): RedirectResponse
    {
        try {
            return $this->to($this->generator->route('home'), $code);

        } catch (RouteNotFoundException) {
            return $this->to('/', $code);
        }
    }

    /**
     * Redirect to a named route.
     *
     * @param string $route
     * @param array $parameters
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     */
    public function route(string $route, array $parameters = [], int $code = 302, array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->route($route, $parameters), $code, $headers);
    }

    /**
     * Redirect to the previous url saved in the session.
     *
     * @param string $fallback
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     */
    public function back(string $fallback = '/', int $code = 302, array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->previous($fallback), $code, $headers);
    }

    /**
     * Redirect to the same url (refresh page).
     *
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     */
    public function refresh(int $code = 302, array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->current(), $code, $headers);
    }

    /**
     * Redirect while saving current url to the session.
     *
     * @param string $url
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     * @throws NotSupportedException
     */
    public function remember(string $url, int $code = 302, array $headers = []): RedirectResponse
    {
        $current = $this->request->getMethod() === 'GET' && !$this->request->isRequestAjax()
            ? $this->generator->current()
            : $this->generator->previous();

        if ($current) {
            $this->generator->getSession()->setIntendedUrl($current);
        }

        return $this->to($url, $code, $headers);
    }

    /**
     * Redirect to the intended url saved in the session.
     *
     * @param string $fallback
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     */
    public function intended(string $fallback = '/', int $code = 302, array $headers = []): RedirectResponse
    {
        return $this->to($this->generator->intended($fallback), $code, $headers);
    }

    /**
     * Redirect to a url in secure mode.
     *
     * @param string $url
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     */
    public function secure(string $url, int $code = 302, array $headers = []): RedirectResponse
    {
        return $this->to($url, $code, $headers, true);
    }

    /**
     * Redirect to a controller action. format "controller@action" || [controller, action]
     *
     * @param string $action
     * @param array $parameters
     * @param array $headers
     * @param int $code
     * @return RedirectResponse
     */
    public function action(string $action, array $parameters = [], array $headers = [], int $code = 302): RedirectResponse
    {
        return $this->to($this->generator->action($action, $parameters), $code, $headers);
    }

    /**
     * Initiate redirect response to any url without url compilation.
     *
     * @param string $url
     * @param int $code
     * @param array $headers
     * @return RedirectResponse
     */
    public function away(string $url, int $code = 302, array $headers = []): RedirectResponse
    {
        return (new RedirectResponse($url, $code, $headers))
            ->setRequest($this->request);
    }

    /**
     * Initiate redirect response to any url.
     *
     * @param string $url
     * @param int $code
     * @param array $headers
     * @param bool|null $secure
     * @return RedirectResponse
     */
    public function to(string $url, int $code = 302, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        return (new RedirectResponse($this->generator->compile($url, $secure), $code, $headers))
            ->setRequest($this->request);
    }
}
