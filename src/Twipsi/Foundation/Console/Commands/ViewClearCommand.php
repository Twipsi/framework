<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'views:clear')]
class ViewClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'views:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete all views cache file';

    /**
     * Handle the command.
     *
     * @return void
     * @throws DirectoryManagerException
     */
    public function handle(): void
    {
        $views = new FileBag($this->app->path('path.cache').'/views');

        if(! $views->empty()) {
            $views->flush(false);

            $this->render->success('The views cache has been deleted');
        } else {
            $this->render->warning('No view cache files found.');
        }
    }
}