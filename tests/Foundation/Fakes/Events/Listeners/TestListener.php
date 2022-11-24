<?php

namespace Twipsi\Tests\Foundation\Fakes\Events\Listeners;

use Twipsi\Components\Events\Interfaces\ListenerInterface;
use Twipsi\Tests\Foundation\Fakes\Events\TestEvent;

class TestListener implements ListenerInterface
{
    /**
     * Dispatch listener logic
     *
     * @param TestEvent $event
     * @return void
     */
    public function resolve(TestEvent $event): void
    {
        echo '[EVENT] => Notification sent.';
    }
}