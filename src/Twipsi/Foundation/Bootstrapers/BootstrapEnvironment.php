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

namespace Twipsi\Foundation\Bootstrapers;

use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\DotEnv;
use Twipsi\Foundation\Exceptions\BootstrapperException;

class BootstrapEnvironment
{
    /**
     * The base .env file path.
     *
     * @var string
     */
    protected string $environment;

    /**
     * The context .env file to load.
     *
     * @var string
     */
    protected string $contextOverride;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Construct Bootstrapper.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->environment = $app->environmentFile();
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws BootstrapperException
     */
    public function invoke(): void
    {
        // Apply any custom context .env file
        $this->applyEnvironmentContext();

        // Load the .env file
        try {
            $this->getDotEnv()->load();

        } catch (FileNotFoundException $e) {
            throw new BootstrapperException($e->getMessage());
        }
    }

    /**
     * Set the environment context from the server global.
     *
     * @return void
     */
    protected function applyEnvironmentContext(): void
    {
        if (! $context = ($_SERVER['APP_ENV'] ?? null)) {
            return;
        }

        $this->setEnvironmentFile(
            $this->environment . '.' . $context
        );
    }

    /**
     * Set the context environment file.
     *
     * @param string $file
     * @return bool
     */
    protected function setEnvironmentFile(string $file): bool
    {
        if (is_file($file)) {
            $this->contextOverride = $file;
            $this->app->setEnvironmentFile($file);

            return true;
        }

        return false;
    }

    /**
     * Create a new dotenv object.
     *
     * @return DotEnv
     * @throws FileNotFoundException
     */
    protected function getDotEnv(): DotEnv
    {
        return new DotEnv($this->contextOverride ?? $this->environment);
    }
}