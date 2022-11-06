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
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ExceptionHandler;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Chronos;

class Kernel
{
    /**
     * The application object.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The console object.
     *
     * @var Console
     */
    protected Console $console;

    /**
     * The datetime when the command was started.
     *
     * @var string|null
     */
    protected string|null $commandStart;

    /**
     * Collection of handlers to manage command life cycles.
     *
     * @var array
     */
    protected array $cycleHandlers = [];

    /**
     * The bootstrap classes.
     *
     * @var array<int|string>
     */
    protected array $bootstrappers = [
        \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
        \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
        \Twipsi\Foundation\Bootstrapers\BootstrapExceptionHandler::class,
    ];

    /**
     * The component bootstrap classes.
     *
     * @var array<int|string>
     */
    protected array $bootcomponents = [
        \Twipsi\Foundation\Bootstrapers\BootstrapAliases::class,
        \Twipsi\Foundation\Bootstrapers\AttachComponentProviders::class,
        \Twipsi\Foundation\Bootstrapers\BootComponentProviders::class
    ];

    /**
     * Construct the kernel.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Resolve the CLI command and return the output.
     *
     * @param ArgvInput $input
     * @param ConsoleOutput $output
     * @return int|ConsoleOutput
     * @throws ApplicationManagerException
     * @throws Exception
     */
    public function run(ArgvInput $input, ConsoleOutput $output): int|ConsoleOutput
    {
        $this->commandStart = Chronos::date()->getDateTime();

        try {
            // Bootstrap the system.
            $this->bootstrapSystem();

            // Dispatch the twipe command resolver.
            return $this->getTwipsiConsole()
                ->run($input, $output);

        } catch (Throwable $e) {

            // Collect and handle exceptions with exception handler.
            $this->handleException($output, $e);

            return 1;
        }
    }

    /**
     * Resolve the CLI command by name and return the output.
     *
     * @param string $command
     * @param array $parameters
     * @param OutputInterface|null $output
     * @return int
     * @throws ApplicationManagerException
     * @throws Exception
     */
    public function call(string $command, array $parameters, OutputInterface $output = null): int
    {
        // Bootstrap the system.
        $this->bootstrapSystem();

        // Dispatch the twipe command resolver.
        return $this->getTwipsiConsole()
            ->call($command, $parameters, $output);
    }

    /**
     * @param string $command
     * @param array $options
     * @return null
     */
    public function queue(string $command, array $options)
    {
        //return CommandQueueManager::queue($command, $options);
    }

    /**
     * Register any handlers to handle command life cycles.
     *
     * @param int $seconds
     * @param Closure $callback
     * @return void
     */
    public function registerCycleHandler(int $seconds, Closure $callback): void
    {
        $this->cycleHandlers[] = ['tolerate' => $seconds, 'handler' => $callback];
    }

    /**
     * Bootstrap the application with the provided bootstraps.
     *
     * @return void
     * @throws ApplicationManagerException
     */
    public function bootstrapSystem(): void
    {
        $this->app->bootstrap($this->bootstrappers);
        $this->app->bootstrap($this->bootcomponents);

        $this->app->components()->loadDeferredComponentProviders();
    }

    /**
     * Create a new twipsi console.
     *
     * @return Console
     */
    protected function getTwipsiConsole(): Console
    {
        return $this->console
            ?? $this->console = (new Console(
                $this->app, $this->app->version()
            ));
    }

    /**
     * Register any commands on the console.
     *
     * @param string ...$commands
     * @return Console
     * @throws ApplicationManagerException
     */
    public function loadCommands(string ...$commands): Console
    {
        return $this->getTwipsiConsole()
            ->resolveCommands($commands);
    }

    /**
     * Get the datetime when the command started.
     *
     * @return string
     */
    public function commandStartedAt(): string
    {
        return $this->commandStart;
    }

    /**
     * Clear the command started at datetime.
     *
     * @return void
     */
    public function clearCommandStartedAt(): void
    {
        $this->commandStart = null;
    }

    /**
     * Handle exception handling.
     *
     * @param ConsoleOutput $output
     * @param Throwable $e
     * @return void
     * @throws ApplicationManagerException
     */
    protected function handleException(ConsoleOutput $output, Throwable $e): void
    {
        $this->app->make(ExceptionHandler::class)->report($e);
        $this->app->make(ExceptionHandler::class)->renderConsoleException($output, $e);
    }

    /**
     * Terminate any terminatable.
     *
     * @param ArgvInput $input
     * @param int $status
     * @return void
     * @throws Exception
     */
    public function terminate(ArgvInput $input, int $status): void
    {
        $this->app->terminate();

        foreach($this->cycleHandlers as $handler) {
            $end = Chronos::date($this->commandStart)->addSeconds($handler['tolerate']);

            if(Chronos::date()->travel($end)->isInPast()) {
                call_user_func_array($handler['tolerate'], [$this->commandStart, $input, $status]);
            }
        }

        $this->clearCommandStartedAt();
    }
}
