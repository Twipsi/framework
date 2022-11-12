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

trait HandlesEnvironment
{
    /**
     * The environment file to load.
     * 
     * @var string
     */
    protected string $env = '.env';

    /**
     * The current environment.
     * 
     * @var Closure
     */
    protected Closure $environment;

    /**
     * Return the base .env file.
     * 
     * @return string
     */
    public function environmentFile(): string
    {
        return $this->path('path.environment').'/'.$this->env;
    }

    /**
     * Set the environment file.
     * 
     * @param string $file
     * @return void
     */
    public function setEnvironmentFile(string $file): void
    {
        $this->env = $file;
    }

    /**
     * Set the environment callback.
     * 
     * @param Closure $callback
     * @return void
     */
    public function setEnvironment(Closure $callback): void
    {
        $this->environment = $callback;
    }

    /**
     * Get the current environment.
     * 
     * @return string
     */
    public function getEnvironment(): string
    {
        return call_user_func($this->environment);
    }

    /**
     * Check if we are in a local environment.
     * 
     * @return bool
     */
    public function isLocal(): bool 
    {
        return $this->getEnvironment() === 'local';
    }

    /**
     * Check if we are in a production environment.
     * 
     * @return bool
     */
    public function isProduction(): bool 
    {
        return $this->getEnvironment() === 'production';
    }

    /**
     * Check if we are in a test environment.
     * 
     * @return bool
     */
    public function isTest(): bool 
    {
        return $this->getEnvironment() === 'testing';
    }
}