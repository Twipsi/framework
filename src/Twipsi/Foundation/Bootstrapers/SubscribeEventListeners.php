<?php

declare (strict_types = 1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Foundation\Bootstrapers;

use InvalidArgumentException;
use ReflectionException;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Env;

class SubscribeEventListeners
{
    use ResolvesListeners;

    /**
     * The path to the event files.
     *
     * @var string
     */
    protected string $path;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Construct Bootstrapper.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->path = $app->path('path.events');
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws FileException|ReflectionException|DirectoryManagerException
     */
    public function invoke(): void
    {
        if(Env::get('CACHE_EVENTS', false)) {

            if($this->app->isEventsCached()) {

                // If we have events cached then just load it in the event subscriber.
                $collection = require $this->app->eventsCacheFile();
            } else {

                // Build the collection and cache it.
                $this->saveCache($collection = $this->collectEventListeners(
                    $this->discover($this->path)
                ));
            }
        }

        // Set the collection on the event subscriber.
        $this->app->get('events')
            ->setCollection($collection ?? $this->collectEventListeners(
                $this->discover($this->path)
        ));
    }

    /**
     * Discover event listeners.
     *
     * @param string $where
     * @return array
     */
    public function discover(string $where): array
    {
        if (!is_dir($where)) {
            throw new InvalidArgumentException(sprintf("Directory [%s] could not be found", $where));
        }

        return (new FileBag($where, 'php'))->list();
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
        (new FileBag($dirname = dirname($this->app->eventsCacheFile())))->put(
            str_replace($dirname, '', $this->app->eventsCacheFile()),
            '<?php return '.var_export($listeners, true).';'
        );
    }
}
