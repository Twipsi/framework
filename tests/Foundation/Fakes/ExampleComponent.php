<?php

namespace Twipsi\Tests\Foundation\Fakes;

use InvalidArgumentException;
use Twipsi\Foundation\ComponentManager;

class ExampleComponent extends ComponentManager
{
    protected function resolve(string $driver): mixed
    {
        return match ($driver) {
            'a_driver' => new ADriver(),
            'b_driver' => new BDriver(),
            'c_driver' => new CDriver(),
            default => throw new InvalidArgumentException(
                sprintf('The requested driver [%s] is not supported', $driver)
            ),
        };
    }

    public function getDefaultDriver(): string
    {
        return $this->default ?? 'c_driver';
    }
}