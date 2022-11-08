<?php

namespace Twipsi\Foundation\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Arr;

trait HandlesCalls
{
    /**
     * Call another command from a command.
     *
     * @param Command|string $command
     * @param array $arguments
     * @return int
     * @throws ApplicationManagerException
     * @throws ExceptionInterface
     */
    public function call(Command|string $command, array $arguments = []): int
    {
        return $this->invoke($command, $arguments, $this->output);
    }

    /**
     * Call another command from a command without output.
     *
     * @param Command|string $command
     * @param array $arguments
     * @return int
     * @throws ApplicationManagerException
     * @throws ExceptionInterface
     */
    public function silent(Command|string $command, array $arguments = []): int
    {
        return $this->invoke($command, $arguments, new NullOutput());
    }

    /**
     * Invoke the command.
     *
     * @param Command|string $command
     * @param array $arguments
     * @param OutputInterface $output
     * @return int
     * @throws ApplicationManagerException
     * @throws ExceptionInterface
     */
    protected function invoke(Command|string $command, array $arguments, OutputInterface $output): int
    {
        $options = Arr::mapPair(
            Arr::only($this->options(), 'ansi', 'no-ansi', 'no-interaction', 'quiet', 'verbose'),
            function($value, $key) {
                return $value ? ["--{$key}" => $value] : null;
        });

        $input = new ArrayInput(array_merge($options, $arguments));

        if(($command = $this->resolve($command)) instanceof Command) {
            $command->run($input, $output);
        }

        return 1;
    }

    /**
     * Resolve a command and return it as a symfony command.
     *
     * @param Command|string $command
     * @return SymfonyCommand|null
     * @throws ApplicationManagerException
     */
    protected function resolve(Command|string $command): ?SymfonyCommand
    {
        // If we provided a command name, attempt to find
        // the registered command in the symfony application.
        if (is_string($command) && !class_exists($command)) {
            return $this->getApplication()->find($command);
        }

        if(is_string($command)) {
            $command = $this->app->make($command);
        }

        $command->setTwipsi($this->app);
        return $command;
    }
}