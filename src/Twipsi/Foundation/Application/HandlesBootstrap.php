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

use Throwable;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

trait HandlesBootstrap
{
    /**
     * If the system has been bootstrapped.
     *
     * @var bool
     */
    protected bool $bootstrapped = false;

    /**
     * If the system has been poststrapped.
     *
     * @var bool
     */
    protected bool $poststrapped = false;

    /**
     * List of the bootrappers.
     *
     * @var array
     */
    protected array $bootstrappers = [];

    /**
     * List of the poststrappers.
     *
     * @var array
     */
    protected array $poststrappers = [];

    /**
     * Bootstrap the system.
     *
     * @param array $bootstrappers
     * @return void
     * @throws ApplicationManagerException
     */
    public function bootstrap(array $bootstrappers): void
    {
        foreach ($bootstrappers as $bootstrap) {

            try {
                $bootstrapper = $this->make($bootstrap);

            } catch (Throwable $e) {
                throw new ApplicationManagerException(
                    sprintf("Could not resolve bootstrapper [%s] with error [%s]", $bootstrap, $e->getMessage())
                );
            }

            $bootstrapper->invoke($this);

            $this->bootstrappers[] = $bootstrap;
        }

        $this->bootstrapped = true;
    }

    /**
     * Poststrap the system.
     *
     * This is mainly used to load bootstrappers that
     * should be loaded after we have the current context from the route.
     *
     * @param array $poststrappers
     * @return void
     * @throws ApplicationManagerException
     */
    public function poststrap(array $poststrappers): void
    {
        foreach ($poststrappers as $poststrap) {

            try {
                $poststrapper = $this->make($poststrap);

            } catch (Throwable) {
                throw new ApplicationManagerException(
                    sprintf("Could not resolve bootstrapper [%s]", $poststrap)
                );
            }

            $poststrapper->invoke($this);

            $this->poststrappers[] = $poststrap;
        }

        $this->poststrapped = true;
    }

    /**
     * Check if we have bootstrapped.
     *
     * @return bool
     */
    public function isBootstrapped(): bool
    {
        return $this->bootstrapped;
    }

    /**
     * Return the bootrappers.
     *
     * @return array
     */
    public function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Check if we have poststrapped.
     *
     * @return bool
     */
    public function isPoststrapped(): bool
    {
        return $this->poststrapped;
    }

    /**
     * Return the bootrappers.
     *
     * @return array
     */
    public function poststrappers(): array
    {
        return $this->poststrappers;
    }
}