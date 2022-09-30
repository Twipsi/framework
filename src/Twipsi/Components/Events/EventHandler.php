<?php
declare (strict_types=1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Components\Events;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Interfaces\ShouldQueue;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Bags\ArrayBag as Container;

final class EventHandler
{
    /**
     * The listeners Container.
     *
     * @var Container
     */
    public Container $listeners;

    /**
     * Listener dispatcher.
     *
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * Construct the subscriber.
     *
     * @param Application|null $app
     */
    public function __construct(Application $app = null)
    {
        $this->listeners = new Container;
        $this->dispatcher = new Dispatcher($this, $app);
    }

    /**
     * Dispatch events.
     *
     * @param EventInterface|string $event
     * @param mixed ...$args
     *
     * @return void
     * @throws ApplicationManagerException
     */
    public function dispatch(EventInterface|string $event, ...$args): void
    {
        $this->dispatcher->dispatch($event, ...$args);
    }

    /**
     * Register an event listener(s).
     *
     * @param string|Closure $event
     * @param string|array|Closure|null $listeners
     *
     * @return void
     * @throws ReflectionException
     */
    public function listen(string|Closure $event, string|array|Closure $listeners = null): void
    {
        // If we provided a closure as an event.
        if ($event instanceof Closure) {

            $this->listen($this->getEventFromClosure($event), $event);
            return;
        }

        // If we are providing a class check if it exists.
        if (!class_exists($event)) {
            throw new InvalidArgumentException(
                sprintf("The provided event [%s] could not be resolved", $event)
            );
        }

        // If we have an array of listeners provided for a specif event.
        if (is_array($listeners)) {
            
            foreach ($listeners as $listener) {
                $this->listen($event, $listener);
            }

            return;
        }
          
        // If it's a closure or a simple class register it.
        $this->listeners->push($event, $listeners);
    }

    /**
     * Extract event class from the closure.
     *
     * @param Closure $closure
     *
     * @return string
     * @throws ReflectionException
     */
    protected function getEventFromClosure(Closure $closure): string
    {
        $reflection = new ReflectionFunction($closure);

        if (!$dependencies = $reflection->getParameters()) {
            throw new InvalidArgumentException("No event class has been provided in the closure");
        }

        return (string)reset($dependencies)->getType();
    }

    /**
     * Return the listener's collection.
     *
     * @return Container|null
     */
    public function listeners(): ?Container
    {
        return $this->listeners;
    }

    /**
     * Set the listener collection.
     *
     * @param array $collection
     *
     * @return void
     */
    public function setCollection(array $collection): void
    {
        $this->listeners->merge($collection);
    }

    /**
     * Send the event to queue worker // @Implement
     *
     * @param ShouldQueue $listener
     * @param string $method
     * @param EventInterface $event
     *
     * @return void
     */
    public function queue(ShouldQueue $listener, string $method, EventInterface $event): void
    {
        //
    }

    /**
     * Send the event to broadcast // @Implement
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function broadcast(EventInterface $event): void
    {
        //
    }
}
