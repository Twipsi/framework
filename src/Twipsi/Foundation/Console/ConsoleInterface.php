<?php

namespace Twipsi\Foundation\Console;

use Closure;
use Exception;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

interface ConsoleInterface
{
    /**
     * Set bootstrapping callbacks.
     *
     * @param Closure $callback
     * @return void
     */
    public static function boot(Closure $callback): void;

    /**
     * Resolve the CLI command and return the output.
     *
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     * @throws Exception
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int;

    /**
     * Resolve the CLI command by name and return the output.
     *
     * @param string $command
     * @param array $parameters
     * @param OutputInterface|null $output
     * @return int
     * @throws Exception
     */
    public function call(string $command, array $parameters, OutputInterface $output = null): int;

    /**
     * Resolve the list of commands.
     *
     * @param array $commands
     * @return Console
     * @throws ApplicationManagerException
     */
    public function resolveCommands(array $commands): Console;

    /**
     * Resolve a command and add it.
     *
     * @param Command|string $command
     * @return SymfonyCommand|null
     * @throws ApplicationManagerException
     */
    public function resolve(Command|string $command): ?SymfonyCommand;
}