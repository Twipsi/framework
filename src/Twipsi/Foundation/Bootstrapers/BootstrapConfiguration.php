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

use ReflectionException;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Env;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\BootstrapperException;

class BootstrapConfiguration
{
    /**
     * The path to the configuration files.
     *
     * @var string
     */
    protected string $path;

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
        $this->path = $app->path('path.config');
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function invoke(): void
    {
        // Bind the configuration closure.
        $this->app->keep('config', $this->lazyLoadConfig(...));

        // Bind the environment to the application.
        $this->app->setEnvironment(
            fn() => $this->app->get('config')
                ->get('system.env', 'production')
        );
    }

    /**
     * Set the configuration loader for later when we have the context.
     *
     * @param Application $app
     * @return ConfigRegistry
     * @throws BootstrapperException
     * @throws FileException|DirectoryManagerException|ApplicationManagerException
     */
    protected function lazyLoadConfig(Application $app): ConfigRegistry
    {
        if(Env::get('CACHE_CONFIG', 'false')) {

            if($this->app->isConfigurationCached()) {

                // If we have a cache file then just initiate it.
                $collection = require $this->app->configurationCacheFile();
            } else {

                // Build a new configuration.
                $this->saveCache($collection = $this->discoverConfigurationFiles(
                    $this->path, $app->getContext()
                ));
            }
        }

        // Build a new configuration.
        $config = new ConfigRegistry($collection ?? $this->discoverConfigurationFiles(
            $this->path, $app->getContext()
        ));

        $this->setSystemDefaults($config);

        return $config;
    }

    /**
     * Discover configuration files.
     *
     * @param string $where
     * @param string|null $context
     * @return array
     * @throws BootstrapperException
     */
    protected function discoverConfigurationFiles(string $where, string $context = null): array
    {
        // Set the context path if we have one.
        if (!is_dir($where = !is_null($context) ? $where . '/' . $context : $where)) {
            throw new BootstrapperException(
                sprintf("Configuration directory [%s] could not be found", $where)
            );
        }

        $config = (new FileBag($where, 'php'))
            ->includeAll();

        if (empty($config ?? []) || !isset($config['system'])) {
            throw new BootstrapperException(
                "The required configuration files are missing"
            );
        }

        return $config;
    }

    /**
     * Save the cache to file.
     *
     * @param array $config
     * @return void
     * @throws FileException|DirectoryManagerException
     */
    protected function saveCache(array $config): void
    {
        (new FileBag($dirname = dirname($this->app->configurationCacheFile())))->put(
            str_replace($dirname, '', $this->app->configurationCacheFile()),
            '<?php return '.var_export($config, true).';'
        );
    }

    /**
     * Set PHP defaults based on configuration.
     *
     * @param ConfigRegistry $config
     * @return void
     */
    protected function setSystemDefaults(ConfigRegistry $config): void
    {
        // Set the default timezone.
        date_default_timezone_set($config->get('system.timezone', 'GMT'));

        // Set the chronos timezone.
        \Twipsi\Support\Chronos::setChronosTimezone(
            $config->get('system.timezone', 'GMT')
        );

        // Set the default encoding
        mb_internal_encoding('UTF-8');
    }
}
