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

namespace Twipsi\Components\Password;

use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Applicables\Configuration;

class PasswordManager
{
    use Configuration;

    /**
     * The password drivers container.
     */
    protected array $driver;

    /**
     * Construct password manager.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Returns the current password driver.
     */
    public function driver(string $name = null): Password
    {
        $name = $name ?? $this->getDefaultDriver();

        // Save the drivers in an array to be accessible later without
        // rebuilding them, while also being able to build another driver version
        return $this->driver[$name] ??
            ($this->driver[$name] = $this->resolve($name));
    }

    /**
     * Build the password driver.
     */
    protected function resolve(string $name): Password
    {
        if (!($config = $this->getConfiguredDriver($name))) {
            throw new NotSupportedException(
                sprintf(
                    "No password configuration found for driver [%s]",
                    $name
                )
            );
        }

        return new Password(
            $this->createTokenProvider($config),
            $this->app->get('auth.manager')->createUserProvider($config->get("provider")),
            $this->config->get(
                "auth.providers." . $config->get("provider") . ".table"
            )
        );
    }

    /**
     * Get configured password driver name.
     */
    public function getConfiguredDriver(string $name = null): ?ConfigRegistry
    {
        $name = $name ?? $this->getDefaultDriver();

        return $this->config->get("auth.password." . $name);
    }

    /**
     * Get default password driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get("auth.default.password");
    }

    /**
     * Create Token provider for password reset.
     */
    protected function createTokenProvider(ConfigRegistry $config): TokenProvider
    {
        $appKey = $this->config->get("security.app_key");

        return new TokenProvider(
            $this->app->get("db.connector")->create($this->config->get("database.driver")),
            $config["table"],
            $appKey,
            $config["expire"],
            $config["throttle"] ?? 0
        );
    }
}
