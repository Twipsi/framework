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

namespace Twipsi\Components\Authentication;

use Closure;
use Twipsi\Components\Authentication\Drivers\Interfaces\AuthDriverInterface;
use Twipsi\Components\Authentication\Drivers\SessionDriver;
use Twipsi\Components\Authentication\Drivers\TokenDriver;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Components\User\UserProvider;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Applicables\Configuration;

class AuthenticationManager
{
    use Configuration;

    /**
     * The closure to retrieve the auth user.
     */
    protected Closure $userLoader;

    /**
     * The authentication drivers container.
     */
    protected array $driver = [];

    /**
     * Construct authentication manager.
     */
    public function __construct(protected Application $app)
    {
        // LazyLoad the authenticated user.
        $this->userLoader = $this->userLoader(...);
    }

    /**
     * Returns the current authentication driver.
     */
    public function driver(string $name = null): AuthDriverInterface
    {
        $name = $name ?? $this->getDefaultDriver();

        // Save the drivers in an array to be accessible later without
        // rebuilding them, while also being able to build another driver version
        return $this->driver[$name] ??
            ($this->driver[$name] = $this->resolve($name));
    }

    /**
     * Build the authentication driver.
     */
    protected function resolve(string $name): AuthDriverInterface
    {
        if (!($config = $this->config->get("auth.drivers." . $name))) {
            throw new NotSupportedException(
                sprintf("No auth configuration found for driver [%s]", $name)
            );
        }

        if (
            method_exists(
                $this,
                $method = "create" . ucfirst($config->get("driver")) . "Driver"
            )
        ) {
            return $this->{$method}($name, $config);
        }

        throw new NotSupportedException(
            sprintf(
                "Authentication driver [%s] is not supported",
                $config->get("driver")
            )
        );
    }

    /**
     * Get default authentication driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get("auth.default.driver");
    }

    /**
     * Return the user lazy loaded to attach to request.
     */
    public function userLoader(string $name = null): ?Authable
    {
        return $this->driver($name)->user();
    }

    /**
     * Get User laoder closure.
     */
    public function getUserLoader(): Closure
    {
        return $this->userLoader;
    }

    /**
     * Create Session based authentication driver.
     */
    public function createSessionDriver(
        string $name,
        ConfigRegistry $config
    ): SessionDriver {
        // Create the user provider method used in configuration.
        $provider = $this->createUserProvider($config->get("provider"));

        // Create the session driver authentication method.
        $driver = new SessionDriver(
            $name,
            $provider,
            $this->app->get("session.store"),
            $this->app->get("request")
        );

        // Set validity duration
        $driver->setRememberDuration($config->get("duration"));

        return $driver;
    }

    /**
     * Create Token based authentication driver for API.
     */
    public function createTokenDriver(string $name, ConfigRegistry $config): TokenDriver
    {
        // Create the user provider method used in configuration.
        $provider = $this->createUserProvider($config->get("provider"));

        // Create the token driver authentication method.
        $driver = new TokenDriver(
            $provider,
            $this->app->get("request"),
            $config->get("api_input_field"),
            $config->get("api_storage_column"),
            $config->get("hash")
        );

        return $driver;
    }

    /**
     * Create The user provider specified.
     */
    public function createUserProvider(string $provider): IUserProvider
    {
        return (new UserProvider(
            $this->app->get("db.connector"),
            $this->config
        ))->create($provider);
    }
}
