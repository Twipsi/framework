<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'config:clear')]
class ConfigClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'config:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete the config cache file';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        if($this->app->isConfigurationCached()) {
            (new FileItem($this->app->configurationCacheFile()))->delete();

            $this->render->success('The configuration cache has been deleted');
        } else {
            $this->render->warning('No configuration cache file found.');
        }
    }
}