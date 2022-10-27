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
use ReflectionMethod;
use ReflectionUnionType;
use RuntimeException;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\FileBag;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Env;
use Twipsi\Support\Bags\ObjectBag;

class SubscribeEventListeners
{
    /**
     * The path to the event files.
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
        $this->path = $app->path('path.events');
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws FileException
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
        $this->app->get('events')->setCollection($collection ?? $this->collectEventListeners(
            $this->discover($this->path)
        ));
    }

    /**
     * Discover event listeners.
     *
     * @param string $where
     *
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
     * Parse the listeners and build the collection.
     *
     * @param array $listeners
     *
     * @return array
     */
    protected function collectEventListeners(array $listeners): array
    {
        $cpath = '\App\Events\Listeners\\';
        foreach ($listeners as $listener) {

            $abs = str_replace([$this->app->path('path.base'), '.php'], '', $cpath.$listener);

            $reflection = new ObjectBag($abs);

            // If we have a normal resolve method used in the listener
            // find the events it can listen to and register them.
            if ($reflection->has('resolve')) {

                // Throw exception if listener does not call an existing event.
                if (is_null($events = $this->listenerResolvesToEvents($reflection, 'resolve'))) {

                    throw new RuntimeException(sprintf(
                        "The event listener [%s] should expect a valid event as the first parameter in the resolve method", $listener
                    ));
                }

                // Register the listener to all the events.
                foreach($events as $event) {
                    $collection[$event->getName()][] = $abs;
                }

            } else {

                // Find all the public methods starting with resolve*
                // and extract the events.
                if (is_null($results = $this->findResolvableMethods($reflection))) {

                    throw new RuntimeException(sprintf(
                        "The event listener [%s] has no resolvable methods defined", $listener
                    ));
                }

                foreach($results as $result) {

                    [$method, $events] = $result;

                    foreach(is_array($events) ? $events : [$events] as $event) {
                        $collection[$event->getName()][] = [$abs, $method];
                    }
                }
            }
        }

        return $collection ?? [];
    }

    /**
     * Get the events the listener methods listen to.
     *
     * @param ObjectBag $reflection
     * @param string $method
     *
     * @return array|null
     */
    protected function listenerResolvesToEvents(ObjectBag $reflection, string $method): null|array
    {
        if(! empty($parameters = $reflection->methodParameters($method)))
        {
            // If we have multiple events we are listening to send back an array.
            if(($types = reset($parameters)->getType()) instanceof ReflectionUnionType) {
                return $types->getTypes();
            }

            return [$types];
        }

        return null;
    }

    /**
     * Find any resolvable classes and their corresponding events.
     *
     * @param ObjectBag $reflection
     *
     * @return array|null
     */
    protected function findResolvableMethods(ObjectBag $reflection): ?array
    {
        if(! empty($methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC)))
        {
            // Filter the methods to start with resolve.
            $methods = array_filter($methods, function($method) {
                return str_starts_with($method->getName(), 'resolve');
            });

            foreach($methods as $method) {

                $types[] = [$method->getName(),
                    $this->listenerResolvesToEvents($reflection, $method->getName())];
            }

            return $types ?? null;
        }

        return null;
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
        (new FileBag($dirname = dirname($this->app->eventsCacheFile())))->put(
                str_replace($dirname, '', $this->app->eventsCacheFile()),
                '<?php return '.var_export($listeners, true).';'
        );
    }
}
