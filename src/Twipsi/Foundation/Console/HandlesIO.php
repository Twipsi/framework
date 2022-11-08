<?php

namespace Twipsi\Foundation\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twipsi\Foundation\Console\Renderer\RenderFactory;

trait HandlesIO
{
    /**
     * The command renderer.
     *
     * @var RenderFactory
     */
    protected RenderFactory $render;

    /**
     * The output symfony style.
     *
     * @var SymfonyStyle
     */
    protected SymfonyStyle $output;

    /**
     * The symfony input interface.
     *
     * @var InputInterface
     */
    protected InputInterface $input;

    /**
     * The verbosity of output commands.
     *
     * @var int
     */
    protected int $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The symfony output verbosity levels.
     *
     * @var array
     */
    protected array $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
    ];

    /**
     * Set the input interface.
     *
     * @param InputInterface $input
     * @return void
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * Set the output style.
     *
     * @param SymfonyStyle $output
     * @return void
     */
    public function setOutput(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    /**
     * Get the input interface.
     *
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * Get the output style.
     *
     * @return SymfonyStyle
     */
    public function getOutput(): SymfonyStyle
    {
        return $this->output;
    }

    /**
     * Set the verbosity.
     *
     * @param int|string $level
     * @return void
     */
    protected function setVerbosity(int|string $level): void
    {
        $this->verbosity = $this->getVerbosity($level);
    }

    /**
     * Get the default or a specific verbosity.
     *
     * @param int|string|null $level
     * @return int
     */
    protected function getVerbosity(int|string|null $level = null): int
    {
        if(is_null($level)) {
            return $this->verbosity;
        }

        if(is_int($level)) {
            return $level;
        }

        return $this->verbosityMap[$level];
    }
}