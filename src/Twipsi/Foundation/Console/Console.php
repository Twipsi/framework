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

namespace Twipsi\Foundation\Console;

use Closure;
use Exception;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class Console extends SymfonyApplication implements ConsoleInterface
{
    /**
     * The twipsi application.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The last command output.
     *
     * @var BufferedOutput|OutputInterface
     */
    protected BufferedOutput|OutputInterface $lastOutput;

    /**
     * Registered bootstrappers.
     *
     * @var array
     */
    protected static array $bootstrappers = [];

    /**
     * Construct Console.
     *
     * @param Application $app
     * @param string $version
     */
    public function __construct(Application $app, string $version)
    {
        parent::__construct('Twipsi Framework', $version);

        $this->app = $app;

        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

        $this->bootstrap();
    }

    /**
     * Set bootstrapping callbacks.
     *
     * @param Closure $callback
     * @return void
     */
    public static function boot(Closure $callback): void
    {
        static::$bootstrappers[] = $callback;
    }

    public static function getBootstrappers(): array
    {
        return static::$bootstrappers;
    }

    /**
     * Bootstrap the console.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            call_user_func($bootstrapper, $this);
        }
    }

    /**
     * Resolve the CLI command and return the output.
     *
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     * @throws Exception
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $name = $this->getCommandName(
            $input = $input ?: new ArgvInput()
        );

        is_null($name) ?: $input->bind($this->find($name)->getDefinition());

        return parent::run($input, $output);
    }

    /**
     * Resolve the CLI command by name and return the output.
     *
     * @param string $command
     * @param array $parameters
     * @param OutputInterface|null $output
     * @return int
     * @throws Exception
     */
    public function call(string $command, array $parameters, OutputInterface $output = null): int
    {
        if(empty($parameters)) {
            $command = $this->getCommandName(
                $input = new StringInput($command)
            );
        } else {
            $input = new ArrayInput($parameters);
        }

        if (! $this->has($command)) {
            throw new CommandNotFoundException(
                sprintf('The requested command "%s" could not be found.', $command)
            );
        }

        return $this->run(
            $input, $this->lastOutput = $output ?? new BufferedOutput
        );
    }

    /**
     * Get the last command output.
     *
     * @return string
     */
    public function lastOutput(): string
    {
        return isset($this->lastOutput)
            ? $this->lastOutput->fetch()
            : '';
    }

    /**
     * Resolve the list of commands.
     *
     * @param array $commands
     * @return $this
     * @throws ApplicationManagerException
     */
    public function resolveCommands(array $commands): Console
    {
        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    /**
     * Resolve a command and add it.
     *
     * @param Command|string $command
     * @return SymfonyCommand|null
     * @throws ApplicationManagerException
     */
    public function resolve(Command|string $command): ?SymfonyCommand
    {
        if ($command instanceof Command) {
            return $this->add($command);
        }

        return $this->add($this->app->make($command));
    }

    /**
     * Add a command to the console.
     *
     * @param SymfonyCommand|Command $command
     * @return SymfonyCommand|null
     */
    public function add(SymfonyCommand|Command $command): ?SymfonyCommand
    {
        if ($command instanceof Command) {
            $command->setTwipsi($this->app);
        }

        return parent::add($command);
    }

    /**
     * Get the default input definition of the console.
     *
     * @return InputDefinition
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $option = new InputOption('--env', null,
            InputOption::VALUE_OPTIONAL, 'Command run environment');

        $parent = parent::getDefaultInputDefinition();

        // Set the --env option for commands
        $parent->addOption($option);

        return $parent;
    }

    /**
     * Get the application instance.
     *
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->app;
    }
}
