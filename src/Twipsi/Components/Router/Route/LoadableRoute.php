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
use Throwable;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;
use Twipsi\Foundation\Application\Application;

final class LoadableRoute extends Route
{
    /**
     * Application instance
     *
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Loadable route constructor.
     *
     * @param string $uri
     * @param Closure $callback
     * @param array $methods
     */
    public function __construct(string $uri, Closure $callback, array $methods)
    {
        $this->app = null;
        parent::__construct($uri, $callback, $methods);
    }

    /**
     * Initiate route rendering and return a valid response.
     *
     * @return ResponseInterface
     * @throws InvalidRouteException
     */
    public function render(): ResponseInterface
    {
        if (!($callback = $this->callback) instanceof Closure) {
            throw new InvalidRouteException("No valid callback provided to the route");
        }

        // Build the callback through the application.
        try {
            return !is_null($this->app)
                ? $this->app->build($callback, $this->getParameterValues())
                : call_user_func($callback, ...$this->getParameterValues());

            // If there was an exception executing the closure then throw invalid route.
        } catch (Throwable $e) {
            throw new InvalidRouteException("Invalid parameters provided for the callback", $e);
        }
    }

    /**
     * Set the application.
     *
     * @param Application|null $app
     * @return LoadableRoute
     */
    public function setApp(?Application $app): LoadableRoute
    {
        $this->app = $app;
        return $this;
    }
}
