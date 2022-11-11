<?php

namespace Twipsi\Foundation\Console\Commands;

use ReflectionException;
use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Bootstrapers\ResolvesListeners;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'events:cache')]
class EventCacheCommand extends Command
{
    use ResolvesListeners;

    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'events:cache';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Cache all the events and their listeners';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileException|ReflectionException
     */
    public function handle(): void
    {
        $repository = $this->discoverEventListeners(
            $this->app->path('path.events')
        );

        if(is_null($repository)) {
            $this->render->error(
                sprintf("Event listeners directory [%s] could not be found", $this->app->path('path.events'))
            );

            return;
        }

        // Cache the events.
        $this->saveCache($this->collectEventListeners($repository));

        $this->render->success('The event listeners have been successfully cached');
    }

    /**
     * Discover event listeners.
     *
     * @param string $where
     * @return array|null
     */
    public function discoverEventListeners(string $where): ?array
    {
        return is_dir($where)
            ? (new FileBag($where, 'php'))->list()
            : null;
    }

    /**
     * Save the cache to file.
     *
     * @param array $listeners
     * @return void
     * @throws FileException
     */
    protected function saveCache(array $listeners): void
    {
        (new FileBag($dirname = dirname($this->app->eventsCacheFile())))->put(
            str_replace($dirname, '', $this->app->eventsCacheFile()),
            '<?php return '.var_export($listeners, true).';'
        );
    }
}