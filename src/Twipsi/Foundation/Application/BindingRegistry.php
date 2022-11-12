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

namespace Twipsi\Foundation\Application;

use Closure;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Bags\ArrayBag as Container;

class BindingRegistry extends Container
{
    /**
     * Singleton abstracts container.
     *
     * @var array
     */
    public array $singletons = [];

    /**
     * Rebindings container.
     *
     * @var array
     */
    public array $rebindings = [];

    /**
     * Extension container.
     *
     * @var array
     */
    public array $extensions = [];

    /**
     * Bindings registry constructor.
     *
     * @param array $bindings
     */
    public function __construct(array $bindings = [])
    {
        parent::__construct($bindings);
    }

    /**
     * Bind a concrete closure to an abstract.
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $singleton
     * @return void
     * @throws ApplicationManagerException
     */
    public function bind(string $abstract, string|Closure $concrete = null, bool $singleton = false): void
    {
        // If singleton is true we will set the instance to be saved.
        if ($singleton) {
            $this->singletons[] = $abstract;
        }

        // If there is no concrete set, use the abstract.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the concrete is not valid or class does not exist exit.
        if (is_string($concrete) && !class_exists($concrete)) {
            throw new ApplicationManagerException(
                sprintf("The provided concrete class does not exist {%s}", $concrete)
            );
        }

        $this->set($abstract, $concrete);
    }

    /**
     * Add a rebinding callback to an abstract.
     *
     * @param string $abstract
     * @param Closure $callback
     * @return void
     */
    public function rebind(string $abstract, Closure $callback): void
    {
        $this->rebindings[$abstract][] = $callback;
    }

    /**
     * Get the rebindings of an abstract.
     *
     * @param string $abstract
     * @return array
     */
    public function rebindings(string $abstract): array
    {
        return $this->rebindings[$abstract] ?? [];
    }

    /**
     * Extend an abstract with a callback.
     *
     * @param string $abstract
     * @param Closure $callback
     * @return void
     */
    public function extend(string $abstract, Closure $callback): void
    {
        $this->extensions[$abstract][] = $callback;
    }

    /**
     * Get the extensions of an abstract.
     *
     * @param string $abstract
     * @return array
     */
    public function extensions(string $abstract): array
    {
        return $this->extensions[$abstract] ?? [];
    }

    /**
     * Check if an abstract is set to be persistent singleton.
     *
     * @param string $abstract
     * @return bool
     */
    public function isPersistent(string $abstract): bool
    {
        return in_array($abstract, $this->singletons);
    }
}
