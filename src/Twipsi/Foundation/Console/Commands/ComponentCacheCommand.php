<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Bootstrapers\ResolveComponentProviders;
use Twipsi\Foundation\Console\Command;
use Twipsi\Support\Bags\SimpleBag as Container;

#[AsCommand(name: 'components:cache')]
class ComponentCacheCommand extends Command
{
    use ResolveComponentProviders;

    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'components:cache';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Cache all the component providers';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileException
     */
    public function handle(): void
    {
        $providers = $this->app->get('config')
            ->get('component.loaders')->all();

        if(empty($providers)) {
            $this->render->warning(
                'No component providers found in the configuration files.'
            );
        }

        // Save the component cache.
        $this->saveCache($this->buildComponentProviderCache($providers));

        $this->render->success('The components have been successfully cached');
    }

    /**
     * Save the cache to file.
     *
     * @param Container $cache
     * @return void
     * @throws FileException
     */
    protected function saveCache(Container $cache): void
    {
        (new FileBag($dirname = dirname($this->app->componentCacheFile())))->put(
            str_replace($dirname, '', $this->app->componentCacheFile()),
            '<?php return '.var_export($cache->all(), true).';'
        );
    }
}