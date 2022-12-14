<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'routes:clear')]
class RouteClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'routes:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete all the route cache file';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        if($this->app->isRoutesCached()) {
            (new FileItem($this->app->routeCacheFile()))->delete();

            $this->render->success('The routes cache has been deleted');
        } else {
            $this->render->warning('No routes cache file found.');
        }
    }
}