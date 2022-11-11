<?php

namespace Twipsi\Foundation\Console\Events;

use Twipsi\Components\Events\Interfaces\EventInterface;
use Twipsi\Components\Events\Traits\Dispatchable;
use Twipsi\Support\Traits\Serializable;

final class CommandStarted implements EventInterface
{
    use Dispatchable, Serializable;

    /**
     * The command called.
     *
     * @var string
     */
    public string $command;

    /**
     * The command options called.
     *
     * @var array
     */
    public array $options;

    /**
     * The command arguments called.
     *
     * @var array
     */
    public array $arguments;

    /**
     * Create a new event data holder.
     *
     * @param string $name
     * @param array $options
     * @param array $arguments
     */
    public function __construct(string $name, array $options = [], array $arguments = [])
    {
        $this->command = $name;
        $this->options = $options;
        $this->arguments = $arguments;
    }

    /**
     * Return the event payload
     *
     * @return array
     */
    public function payload(): array
    {
        return ['command' => $this->command, 'options' => $this->options, 'arguments' => $this->arguments];
    }
}