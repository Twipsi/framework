<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'events:clear')]
class EventClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'events:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete the event cache file';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        if($this->app->isEventsCached()) {
            (new FileItem($this->app->eventsCacheFile()))->delete();

            $this->render->success('The events cache has been deleted');
        } else {
            $this->render->warning('No events cache file found.');
        }
    }
}