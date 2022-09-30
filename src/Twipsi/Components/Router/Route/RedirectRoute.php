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

use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Http\Response\RedirectResponse;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;
use Twipsi\Components\Url\UrlGenerator;
use Twipsi\Support\Str;

final class RedirectRoute extends Route
{
    /**
     * The url generator.
     *
     * @var UrlGenerator
     */
    protected UrlGenerator $generator;

    /**
     * The destination to redirect.
     *
     * @var string|null
     */
    protected string|null $destination;

    /**
     * The http status code.
     *
     * @var int|null
     */
    protected int|null $code;

    /**
     * Redirect route constructor
     *
     * @param string $uri
     * @param array $methods
     */
    public function __construct(string $uri, array $methods)
    {
        parent::__construct($uri, null, $methods);
    }

    /**
     * Initiate route rendering and return a valid response.
     *
     * @return ResponseInterface
     */
    public function render(): ResponseInterface
    {
        if (!isset($this->destination) || !isset($this->code)) {
            throw new InvalidRouteException(sprintf("No destination url set for redirecting %s", $this->getUrl()));
        }

        $values = $this->getParameterValues();

        // Check if the destination is a named route and resolve it.
        if (! Str::hay($this->destination)->has('/')) {
            try {
                $url = $this->generator->route($this->destination, $values);

            } catch (RouteNotFoundException) {
                throw new InvalidRouteException(sprintf("Redirect destination '%s' could not be resolved.", $this->destination));
            }
        }

        // Build a new loadable route based on the destination uri.
        $destination = new LoadableRoute($this->destination, function ($to, $code) {
            return new RedirectResponse($to, $code);
        }, ['GET']);

        // Generate the url based on current scope parameters if it's an uri.
        if (is_null($url = $url ?? $this->generator->fromRoute($destination, $values))) {
            throw new InvalidRouteException(sprintf("Could not resolve destination url %s", $this->destination));
        }

        // Add build url with parameters as a default for rendering.
        return $destination
                ->default(['to' => $url, 'code'=>  $this->code])
                ->render();
    }

    /**
     * Set the redirection destination fluently.
     *
     * @param string $uri
     * @return RedirectRoute
     */
    public function destination(string $uri): RedirectRoute
    {
        $this->destination = Str::hay($uri)->has('/')
            ? '/' . trim($uri, '/')
            : trim($uri, '/');

        return $this;
    }

    /**
     * Set the redirection status code fluently.
     *
     * @param int $code
     * @return RedirectRoute
     */
    public function code(int $code): RedirectRoute
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set the UrlGenerator.
     *
     * @param UrlGenerator $generator
     * @return RedirectRoute
     */
    public function setGenerator(UrlGenerator $generator): RedirectRoute
    {
        $this->generator = $generator;
        return $this;
    }
}
