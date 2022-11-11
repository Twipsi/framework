<?php

namespace Twipsi\Foundation\Console\Commands;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'config:cache')]
class ConfigCacheCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'config:cache';

    /**
     * Command Arguments.
     *
     * @var array|array[]
     */
    protected array $arguments = [
        ['context', InputArgument::OPTIONAL, 'The configuration context to cache'],
    ];

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Cache the configuration cache files';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileException
     */
    public function handle(): void
    {
        $context = $this->argument('context') ?? null;

        $repository = $this->discoverConfigurationFiles(
            $this->app->path('path.config'), $context
        );

        if(is_null($repository)) {
            $this->render->error(
                sprintf("The configuration context [%s] could not be found", $context ?? 'default')
            );

            return;
        }

        // Cache the configuration.
        $this->saveCache($repository);

        $this->render->success('The configuration has been successfully cached');
    }

    /**
     * Discover configuration files.
     *
     * @param string $where
     * @param string|null $context
     * @return array|null
     */
    protected function discoverConfigurationFiles(string $where, string $context = null): ?array
    {
        // Set the context path if we have one.
        $where = !is_null($context) ? $where . '/' . $context : $where;

        if (!is_dir($where)) {
            return null;
        }

        $config = (new FileBag($where, 'php'))->includeAll();

        if (empty($config ?? []) || !isset($config['system'])) {
            throw new RuntimeException(
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
     * @throws FileException
     */
    protected function saveCache(array $config): void
    {
        (new FileBag($dirname = dirname($this->app->configurationCacheFile())))->put(
            str_replace($dirname, '', $this->app->configurationCacheFile()),
            '<?php return '.var_export($config, true).';'
        );
    }
}