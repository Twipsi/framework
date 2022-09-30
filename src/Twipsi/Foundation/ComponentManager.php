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

namespace Twipsi\Foundation;

use Closure;
use Twipsi\Foundation\Application\Application;

abstract class ComponentManager
{
    /**
     * The driver's container.
     * 
     * @var array<string,object>
     */
    protected array $drivers = [];

    /**
     * Set a custom default driver.
     * 
     * @var string|null
     */
    protected ?string $default = null;

    /**
     * Custom set resolvers to extend.
     *
     * @var array<string, Closure>
     */
    protected array $customResolvers = [];

    /**
     * Construct component manager.
     * 
     * @param Application $app
     */
    public function __construct(protected Application $app){}

    /**
     * Returns the current driver from the container.
     * 
     * @param string|null $driver
     * 
     * @return mixed
     */
    public function driver(string $driver = null): mixed
    {
        $driver = $driver 
            ?? $this->default 
            ?? $this->getDefaultDriver();

        // Save the drivers in an array to be accessible later without
        // rebuilding them, while also being able to build another driver version
        return $this->drivers[$driver] ??
            ($this->drivers[$driver] = $this->resolve($driver));
    }

    /**
     * Resolve custom set driver resolvers.
     * 
     * @param string $driver
     * @param mixed ...$args
     * 
     * @return mixed
     */
    public function resolveCustomDriver(string $driver, mixed ...$args): mixed 
    {
        if(isset($this->customResolvers[$driver])) {
            return call_user_func($this->customResolvers[$driver], ...$args);
        }

        return null;
    }
    
    /**
     * Forget an already resolved driver. 
     * 
     * @param string $driver
     * 
     * @return void
     */
    public function forget(string $driver): void
    {
        unset($this->drivers[$driver]);
    }

    /**
     * Check if we have a resolved driver.
     * 
     * @param string $driver
     * 
     * @return bool
     */
    public function has(string $driver): bool
    {
        return isset($this->drivers[$driver]);
    }

    /**
     * Add a custom resolver to a driver.
     * 
     * @param string $driver
     * @param Closure $callback
     * 
     * @return void
     */
    public function extend(string $driver, Closure $callback): void
    {
        $this->customResolvers[$driver] = $callback;
    }

    /**
     * Set a custom default driver.
     * 
     * @param string $driver
     * 
     * @return void
     */
    public function setDefaultDriver(string $driver): void 
    {
        $this->default = $driver;
    }

    /**
     * Forward calls to the actual driver if needed.
     * 
     * @param string $method
     * @param array<int,mixed> $parameters
     * 
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->{$method}(...$parameters);
    }

    /**
     * Resolves the requested driver.
     * 
     * @param string $driver
     * 
     * @return mixed
     */
    abstract protected function resolve(string $driver): mixed;

     /**
     * Gets the default driver set that should be used.
     * 
     * @return string
     */
    abstract public function getDefaultDriver(): string;
}