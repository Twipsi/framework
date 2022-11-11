<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

#[AsCommand(name: 'routes:cache')]
class RouteCacheCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'routes:cache';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Cache all the routes';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileException
     * @throws ExceptionInterface
     * @throws ApplicationManagerException|DirectoryManagerException
     */
    public function handle(): void
    {
        // Flush the previous cache.
        $this->silent('routes:clear');

        $repository = $this->discoverRoutes(
            $this->app->path('path.routes')
        );

        if(is_null($repository)) {
            $this->render->error(
                sprintf("Route directory [%s] could not be found", $this->app->path('path.routes'))
            );

            return;
        }

        // Cache the routes.
        $this->saveCache($this->app->get('route.factory')
            ->routes()->packRoutes()
        );

        $this->render->success('The routes have been successfully cached');
    }

    /**
     * Discover routes.
     *
     * @param string $where
     * @return array|null
     */
    protected function discoverRoutes(string $where): ?array
    {
        return is_dir($where)
            ? (new FileBag($where, 'php'))->includeAll()
            : null;
    }

    /**
     * Save the cache to file.
     *
     * @param array $listeners
     * @return void
     * @throws FileException|DirectoryManagerException
     */
    protected function saveCache(array $listeners): void
    {
        (new FileBag($dirname = dirname($this->app->routeCacheFile())))->put(
            str_replace($dirname, '', $this->app->routeCacheFile()),
            '<?php return '.var_export($listeners, true).';'
        );
    }
}