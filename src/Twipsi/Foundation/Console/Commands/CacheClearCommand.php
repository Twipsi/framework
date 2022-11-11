<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

#[AsCommand(name: 'cache:clear')]
class CacheClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'cache:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete all cache files across the application';

    /**
     * Handle the command.
     *
     * @return void
     * @throws ExceptionInterface
     * @throws ApplicationManagerException|DirectoryManagerException
     */
    public function handle(): void
    {
        $this->silent('boot:clear');
        $this->silent('views:clear');

        $cache = new FileBag($this->app->path('path.cache').'/application');

        if(! $cache->empty()) {
            $cache->flush(false);
        }

        $this->render->success('All cache files have been deleted');
    }
}