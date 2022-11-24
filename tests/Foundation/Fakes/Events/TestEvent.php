<?php

namespace Twipsi\Tests\Foundation\Fakes\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;

class TestEvent implements EventInterface
{
    public function payload(): array
    {
        return [];
    }
}