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
use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Interfaces\ListenerInterface;
use Twipsi\Components\Events\Interfaces\ShouldQueue;

final class Listener
{
    /**
     * The dispatcher instance.
     *
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * The build closure to load the listener.
     *
     * @var Closure|array
     */
    protected Closure|array $callable;

    /**
     * The original listener.
     *
     * @var string|Closure
     */
    protected string|Closure $listener;

    /**
     * The listener method to use.
     *
     * @var string|null
     */
    protected string|null $method;

    /**
     * Build the listener item.
     *
     * @param Dispatcher $dispatcher
     * @param string|Closure $listener
     * @param string|null $method
     */
    public function __construct(Dispatcher $dispatcher, string|Closure $listener, string $method = null)
    {
        $this->method = $method;
        $this->listener = $listener;
        $this->dispatcher = $dispatcher;

        $this->buildCallable($listener);
    }

    /**
     * Parse the listener type and save it accordingly.
     * 
     * @param string|Closure $listener
     * 
     * @return void
     */
    public function buildCallable(string|Closure $listener): void
    {
        // If we have a closure provided, so we will
        // build a callable from the closure.
        if ($listener instanceof Closure) {

            $this->callable = $listener;
        }

        // Otherwise create the listener.
        $this->callable = $this->createListener($listener);
    }

    /**
     * Resolve the listener callable.
     * 
     * @param EventInterface $instance
     * 
     * @return void
     */
    public function call(EventInterface $instance): void
    {
        call_user_func($this->callable, $instance);
    }

    /**
     * Create the listener callable.
     *
     * @param string $listener
     *
     * @return callable
     */
    protected function createListener(string $listener): callable
    {
        [$class, $method] = [$listener, $this->method];

        // If the method doesn't exist we will use the abstract method.
        if(is_null($method) || ! method_exists($class, $method)) {
            $method = 'resolve';
        }

        // Create the listener instance.
        $instance = $this->dispatcher->makeListener($class);

        // Check if the listener implements the ShouldQueue interface
        // and send it to the queue resolver if queueIf meets condition
        // otherwise it won't be queued or dispatched.
        if($this->shouldQueue($instance)) {
            return $this->createQueuedListener($instance, $method);
        }

        return [$instance, $method];
    }

    /**
     * Create queued listener callable.
     * 
     * @param ShouldQueue $listener
     * @param string $method
     * 
     * @return Closure
     */
    protected function createQueuedListener(ShouldQueue $listener, string $method): Closure
    {
        return function($instance) use ($listener, $method) {

            if($this->queueConditionIsMet($listener, $instance)) {
                $this->dispatcher->sendToQueueWorker($listener, $method, $instance);
            }
        };
    }

    /**
     * Check if the listener should be queued.
     * 
     * @param ListenerInterface $listener
     * 
     * @return bool
     */
    protected function shouldQueue(ListenerInterface $listener): bool 
    {
        return ($listener instanceof ShouldQueue);
    }

    /**
     * Check if the listener meets the queue condition.
     * 
     * @param ShouldQueue $listener
     * @param EventInterface $event
     * 
     * @return bool
     */
    protected function queueConditionIsMet(ShouldQueue $listener, EventInterface $event): bool
    {
        return $listener->queueIf($event);
    }

    /**
     * Get the original listener.
     *
     * @return string|null
     */
    public function getListener(): ?string
    {
        return $this->listener;
    }

    /**
     * Get the build callable.
     *
     * @return Closure|null
     */
    public function getCallable(): ?Closure
    {
        return $this->callable;
    }

    /**
     * Get listener method.
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }
}
