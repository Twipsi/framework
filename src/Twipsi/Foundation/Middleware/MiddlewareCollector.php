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

namespace Twipsi\Foundation\Middleware;

use RuntimeException;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Support\Bags\SimpleBag as Container;
use Twipsi\Support\Str;

class MiddlewareCollector extends Container
{
    /**
     * Middleware subscriber.
     *
     * @var MiddlewareRepository
     */
    protected MiddlewareRepository $repository;

    /**
     * Construct our middleware collector.
     *
     * @param MiddlewareRepository $repository
     */
    public function __construct(MiddlewareRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    /**
     * Collect all the middlewares needed to be executed.
     *
     * @param Route $route
     * @return $this
     */
    public function build(Route $route): MiddlewareCollector
    {
        // Collect all the general middlewares.
        empty($general = $this->collectGeneralMiddlewares())
            ?: $this->set('general', $general);

        // Collect all group and route middlewares applied.
        foreach ($this->collectRouteMiddlewares($route) as $middleware) {

            // If the middleware is registered as a group, then
            // add all the middlewares to the stack registered in the group.
            if ($this->repository->hasGroupMiddleware($middleware)) {

                foreach ($this->collectGroupMiddlewares($middleware) as $value) {
                    $this->push('group', $value);
                }

                continue;
            }

            // If the middleware is a single route middleware, then
            // add it to the stack with the custom arguments.
            if ($this->repository->hasSingleMiddleware($middleware)) {

                $class = $this->collectSingleMiddleware($middleware);
                $this->push('single', $this->extractArguments($class));

                continue;
            }

            // If the middleware is a custom callable class, then
            // add it to the stack with the custom arguments.
            $extracted = $this->extractArguments($middleware);

            if (class_exists($extracted[0])) {
                $this->push('custom', $extracted);
                continue;
            }

            throw new RuntimeException(sprintf('Middleware [%s] could not be found', $middleware));
        }

        return $this;
    }

    /**
     * Extract the arguments from the string if any.
     *
     * @param string $middleware
     * @return array
     */
    protected function extractArguments(string $middleware): array
    {
        if (Str::hay($middleware)->contains('@')) {
            [$middleware, $arguments] = explode('@', $middleware);
            $arguments = explode(',', $arguments);
        }

        return [$middleware, $arguments ?? null];
    }

    /**
     * Collect all the general middlewares.
     *
     * @return array
     */
    protected function collectGeneralMiddlewares(): array
    {
        return $this->repository->getGeneralMiddlewares();
    }

    /**
     * Collect all the group middlewares.
     *
     * @param string $middleware
     * @return array|null
     */
    protected function collectGroupMiddlewares(string $middleware): ?array
    {
        return $this->repository->getGroupMiddlewares($middleware);
    }

    /**
     * Collect all the single middlewares.
     *
     * @param string $middleware
     * @return string|null
     */
    protected function collectSingleMiddleware(string $middleware): ?string
    {
        return $this->repository->getSingleMiddleware($middleware);
    }

    /**
     * Collect all the route and route group middlewares.
     *
     * @param Route $route
     * @return array|null
     */
    protected function collectRouteMiddlewares(Route $route): ?array
    {
        return $route->getMiddlewares();
    }

    public function setRepository(MiddlewareRepository $repository): MiddlewareCollector
    {
        $this->repository = $repository;

        return $this;
    }
}
