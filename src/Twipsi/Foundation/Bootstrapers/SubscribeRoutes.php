<?php

namespace Twipsi\Foundation\Bootstrapers;

use InvalidArgumentException;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Env;

class SubscribeRoutes
{
    /**
     * The path to the route files.
     *
     * @var string
     */
    protected string $path;

    /**
     * Construct Bootstrapper.
     *
     * @param Application $app
     */
    public function __construct(protected Application $app)
    {
        $this->path = $app->path('path.routes');
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws FileException
     */
    public function invoke(): void
    {
        if(Env::get('CACHE_ROUTES', false)) {

            if($this->app->isRoutesCached()) {

                // If we have routes cached then just load it in the route bag.
                $collection = require $this->app->routeCacheFile();
                $this->app->get('route.routes')->unpackRoutes($collection);

            } else {

                // Load and run the routes.
                $this->loadRoutes($this->path);

                // Build the collection and cache it.
                $this->saveCache(
                    $collection = $this->app->get('route.routes')->packRoutes()
                );
            }
        }

        // If we didn't cache load the routes otherwise do nothing.
        isset($collection) ?: $this->loadRoutes($this->path);
    }

    /**
     * Load and run all the routes found.
     *
     * @param string $where
     * @return void
     */
    public function loadRoutes(string $where): void
    {
        if (!is_dir($where)) {
            throw new InvalidArgumentException(sprintf("Directory [%s] could not be found", $where));
        }

        (new FileBag($where, 'php'))->includeAll();
    }

    /**
     * Save the cache to file.
     *
     * @param array $listeners
     *
     * @return void
     * @throws FileException
     */
    protected function saveCache(array $listeners): void
    {
        (new FileBag($dirname = dirname($this->app->routeCacheFile())))->put(
            str_replace($dirname, '', $this->app->routeCacheFile()),
            '<?php return '.var_export($listeners, true).';'
        );
    }
}
