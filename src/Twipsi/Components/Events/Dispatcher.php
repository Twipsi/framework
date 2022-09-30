<?php
declare(strict_types=1);

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
use RuntimeException;
use Throwable;
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Interfaces\ListenerInterface;
use Twipsi\Components\Events\Interfaces\ShouldBroadcast;
use Twipsi\Components\Events\Interfaces\ShouldQueue;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

final class Dispatcher
{
    /**
     * Eventhandler object.
     * 
     * @var EventHandler
     */
    protected EventHandler $handler;

    /**
     * Application object.
     * 
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Construct Event Dispatcher.
     * 
     * @param EventHandler $handler
     * @param Application|null $app
     */
    public function __construct(EventHandler $handler, Application $app = null)
    {
        $this->handler = $handler;
        $this->app = $app;
    }

    /**
     * Dispatch the event.
     *
     * @param EventInterface|string $event
     * @param mixed ...$payload
     *
     * @return void
     * @throws ApplicationManagerException
     */
    public function dispatch(EventInterface|string $event, ...$payload): void
    {
        // First if we have an instantiated event provided as an event
        // we will retrieve the event class name and set the event object
        // as our current instance otherwise build it.
        [$event, $instance] = $this->parseProvidedEvent($event, $payload);

        // If the event should be broadcast send it to the broadcaster.
        if($this->shouldBroadcast($instance)) {
            $this->sendToBroadcast($instance);
        }

        // Iterate through the listeners and resolve them all.
        foreach ($this->buildListeners($event) as $listener) {

            // If event is stoppable and has been stopped.
            if ($this->isStopped($instance)) {
                break;
            }

            $listener->call($instance);
        }
    }

    /**
     * Parse the event and get the name|instance
     *
     * @param EventInterface|string $event
     * @param array $payload
     *
     * @return array
     * @throws ApplicationManagerException
     */
    protected function parseProvidedEvent(EventInterface|string $event, array $payload): array
    {
        if(is_object($event)) {
            return [get_class($event), $event];
        }

        if (is_string($event) && ! class_exists($event)) {
            throw new RuntimeException(sprintf(
                "class [%s] does not exist", $event
            ));
        }

        // If we are providing a class for an event,
        // build the event from the IOC container using the payload.
        $instance = !is_null($this->app)
                ? $this->app->make($event, $payload)
                : new $event(...$payload);

        return [$event, $instance];
    }

    /**
     * Build the event listener.
     *
     * @param string $listener
     *
     * @return ListenerInterface
     * @throws ApplicationManagerException
     */
    public function makeListener(string $listener): ListenerInterface
    {
        return !is_null($this->app) ? $this->app->make($listener) : (new $listener());
    }

    /**
     * Collect and build all the listeners.
     * 
     * @param string $event
     * 
     * @return array
     */
    protected function buildListeners(string $event): array
    {
        foreach ($this->handler->listeners()?->get($event) ?? [] as $listener) {
            $collection[] = $this->buildListenerManager($listener);
        }

        return $collection ?? [];
    }

    /**
     * Build the listener based on the provided type.
     * 
     * @param string|array|Closure $listener
     * 
     * @return Listener
     */
    protected function buildListenerManager(string|array|Closure $listener): Listener
    {
      // If we only have the listener class name provided and no custom method 
      // to resolve the listener we will use the default "resolve" method.
      if (is_string($listener)) {
        return new Listener($this, $listener, 'resolve');
      }
  
      // If we have an array then we should be receiving a custom method
      // to resolve the listener, so we will set the listener to listen to that method.
      if (is_array($listener)) {
        return new Listener($this, $listener[0], $listener[1] ?? 'resolve');
      }
  
      // Otherwise we have a closure provided, so we will register it as the listener
        return new Listener($this, $listener);
    }

    /**
     * Check if the listener should be broadcast.
     * 
     * @param EventInterface $event
     * 
     * @return bool
     */
    protected function shouldBroadcast(EventInterface $event): bool 
    {
        try {
            return ($event instanceof ShouldBroadcast) 
                && $event->broadcastIf();

        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Check if event propagation has been stopped.
     *
     * @param EventInterface $event
     *
     * @return bool
     */
    protected function isStopped(EventInterface $event): bool
    {
        if (method_exists($event, 'isStopped')) {
            return $event->isStopped();
        }

        return false;
    }

    /**
     * Sent the event to broadcast.
     * 
     * @param EventInterface $event
     * 
     * @return void
     */
    public function sendToBroadcast(EventInterface $event): void
    {
        $this->handler->broadcast($event);
    }

    /**
     * Sent the event to a queue worker.
     * 
     * @param ShouldQueue $listener
     * @param string $method
     * @param EventInterface $event
     * 
     * @return void
     */
    public function sendToQueueWorker(ShouldQueue $listener, string $method, EventInterface $event): void
    {
        $this->handler->queue($listener, $method, $event);
    }
}
