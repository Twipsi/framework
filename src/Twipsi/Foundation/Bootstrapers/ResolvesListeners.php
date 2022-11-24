<?php

namespace Twipsi\Foundation\Bootstrapers;

use ReflectionException;
use ReflectionMethod;
use ReflectionUnionType;
use RuntimeException;
use Twipsi\Support\Bags\ObjectBag;
use Twipsi\Support\Str;

trait ResolvesListeners
{
    /**
     * Parse the listeners and build the collection.
     *
     * @param array $listeners
     * @return array
     * @throws ReflectionException
     */
    protected function collectEventListeners(array $listeners): array
    {
        $classpath = $this->app->applicationNamespace().'\Events\Listeners\\';

        foreach ($listeners as $listener) {

            $strip = explode(DIRECTORY_SEPARATOR, $listener);
            $abs = str_replace(
                '.php', '', $classpath.end($strip)
            );

            $reflection = new ObjectBag($abs);

            // If we have a normal resolve method used in the listener,
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
     * @return array|null
     * @throws ReflectionException
     */
    protected function listenerResolvesToEvents(ObjectBag $reflection, string $method): null|array
    {
        if(! empty($parameters = $reflection->methodParameters($method))) {

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
     * @return array|null
     * @throws ReflectionException
     */
    protected function findResolvableMethods(ObjectBag $reflection): ?array
    {
        if(! empty($methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC))) {

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
}