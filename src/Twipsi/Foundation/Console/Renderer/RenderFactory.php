<?php

namespace Twipsi\Foundation\Console\Renderer;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RenderFactory
{
    /**
     * The output symfony style.
     *
     * @var SymfonyStyle
     */
    protected SymfonyStyle $output;

    /**
     * Construct the command renderer.
     */
    public function __construct(SymfonyStyle $output)
    {
        $this->output = $output;
    }

    /**
     * Create the command renderer.
     *
     * @param string $renderer
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    protected function create(string $renderer, string $message, int $verbosity): void
    {
        if(! class_exists($renderer)) {
            throw new InvalidArgumentException(sprintf(
                'Command renderer "%s" could not be found', $renderer
            ));
        }

        (new $renderer($this->output))->render($message, $verbosity);
    }

    /**
     * Render a debug unformatted message.
     *
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    public function debug(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $class = \Twipsi\Foundation\Console\Renderer\Debug::class;
        $this->create($class, $message, $verbosity);
    }

    /**
     * Render a plain message.
     *
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    public function plain(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $class = \Twipsi\Foundation\Console\Renderer\Plain::class;
        $this->create($class, $message, $verbosity);
    }

    /**
     * Render an info message.
     *
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    public function info(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $class = \Twipsi\Foundation\Console\Renderer\Info::class;
        $this->create($class, $message, $verbosity);
    }
}