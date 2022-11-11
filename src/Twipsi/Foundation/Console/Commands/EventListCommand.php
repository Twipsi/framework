<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

#[AsCommand(name: 'events:list')]
class EventListCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'events:list';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'List all the events and their listeners';

    /**
     * Handle the command.
     *
     * @return void
     * @throws ExceptionInterface
     * @throws ApplicationManagerException
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $cacheExisted = true;

        if(! $this->app->isEventsCached()) {
            $cacheExisted = false;
            $this->silent('events:cache');
        }

        $events = (new FileItem($this->app->eventsCacheFile()));

        // If the cache didst exist delete it.
        if(! $cacheExisted) {
            $this->silent('events:clear');
        }

        if(empty($processed = $this->processEvents($events))) {
            $this->render->warning('No events were found.');
        }

        $this->table(['Event', 'Listeners'], $processed);
    }

    /**
     * Process the events cache file.
     *
     * @param FileItem $file
     * @return array
     */
    protected function processEvents(FileItem $file): array
    {
        foreach ($file->require() as $event => $listeners) {
            foreach ($listeners as $listener) {

                $event = array_values(array_reverse(explode('\\', $event)))[0];

                if(is_array($listener)) {
                    foreach ($listener as $key => $listen) {
                        $rows[] = [$event.'_'.$key, array_values(array_reverse(explode('\\', $listen)))[0]];
                    }
                } else {
                    $rows[] = [$event, array_values(array_reverse(explode('\\', $listener)))[0]];
                }
            }
        }

        return $rows ?? [];
    }
}