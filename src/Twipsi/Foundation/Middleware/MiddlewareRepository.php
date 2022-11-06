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

final class MiddlewareRepository
{
    /**
     * General middlewares container.
     *
     * @var array
     */
    protected array $generalMiddlewares = [];

    /**
     * Group middlewares.
     *
     * @var array
     */
    protected array $groupMiddlewares = [];

    /**
     * Single middlewares.
     *
     * @var array
     */
    protected array $singleMiddlewares = [];

    /**
     * Construct middleware repository.
     *
     * @param array $general
     * @param array $group
     * @param array $single
     */
    public function __construct(array $general, array $group, array $single)
    {
        $this->generalMiddlewares = $general;
        $this->groupMiddlewares = $group;
        $this->singleMiddlewares = $single;
    }

    /**
     * Return general middlewares.
     *
     * @return array|null
     */
    public function getGeneralMiddlewares(): ?array
    {
        return $this->generalMiddlewares;
    }

    /**
     * Return a group from group middlewares.
     *
     * @param string $name
     * @return array|null
     */
    public function getGroupMiddlewares(string $name): ?array
    {
        return $this->groupMiddlewares[$name] ?? null;
    }

    /**
     * Return a middleware from route middlewares.
     *
     * @param string $name
     * @return string|null
     */
    public function getSingleMiddleware(string $name): ?string
    {
        return $this->singleMiddlewares[$name] ?? null;
    }

    /**
     * Check if middleware group exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasGeneralMiddleware(string $name): bool
    {
        return isset($this->generalMiddlewares[$name]);
    }

    /**
     * Check if middleware group exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasGroupMiddleware(string $name): bool
    {
        return isset($this->groupMiddlewares[$name]);
    }

    /**
     * Check if single middleware exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasSingleMiddleware(string $name): bool
    {
        return isset($this->singleMiddlewares[$name]);
    }
}
