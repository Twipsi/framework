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
use Twipsi\Support\Bags\ArrayBag as Container;
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
     * Collect all the middlewares needed to be executed
     */
    public function build(Route $route): MiddlewareCollector
    {
        // Collect all the general middlewares.
        $this->set('general', $this->collectGeneralMiddlewares());

        // Collect all group and route middlewares applied.
        foreach ($this->collectRouteMiddlewares($route) as $middleware) {

            $arguments = null;

            // If the middleware is registered as a group, then
            // add all the middlewares to the stack registered in the group.
            if ($this->repository->hasGroupMiddleware($middleware)) {

                foreach ($this->collectGroupMiddlewares($middleware) as $value) {
                    $this->push('group', $value);
                }
                continue;
            }

            // If it's a single or custom middleware try custom arguments.
            if (Str::hay($middleware)->contains('@')) {
                [$middleware, $arguments] = explode('@', $middleware);
                $arguments = explode(',', $arguments);
            }

            // If the middleware is a single route middleware, then
            // add it to the stack with the custom arguments.
            if ($this->repository->hasSingleMiddleware($middleware)) {
                $this->push('single',
                    [$this->collectSingleMiddlewares($middleware), $arguments ?? null]);
                continue;
            }

            // If the middleware is a custom callable class, then
            // add it to the stack with the custom arguments.
            if (class_exists($middleware)) {
                $this->push('custom', [$middleware, $arguments ?? null]);
                continue;
            }

            throw new RuntimeException(sprintf('Middleware [%s] could not be found', $middleware));
        }

        return $this;
    }


    /**
     * Collect all the general middlewares.
     *
     * @return array|null
     */
    protected function collectGeneralMiddlewares(): ?array
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
    protected function collectSingleMiddlewares(string $middleware): ?string
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
}
