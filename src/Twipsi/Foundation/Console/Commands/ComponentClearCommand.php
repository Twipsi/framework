<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'components:clear')]
class ComponentClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'components:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete the components cache file';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        if($this->app->isComponentsCached()) {
            (new FileItem($this->app->componentCacheFile()))->delete();

            $this->render->success('The components cache has been deleted');
        } else {
            $this->render->warning('No components cache file found.');
        }
    }
}