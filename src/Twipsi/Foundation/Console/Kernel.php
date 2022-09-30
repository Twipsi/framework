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

use Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Console\Commands\TestCommand;
use Twipsi\Foundation\ExceptionHandler;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class Kernel
{
    /**
     * The twipsi application.
     *
     * @var Application
     */
    protected Application $app;

    protected array $commands = [
      'test' => TestCommand::class
    ];

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
     * Construct the kernel.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Create a new twipsi console.
     *
     * @return Console
     * @throws ApplicationManagerException
     */
    protected function getTwipsiConsole(): Console
    {
        return (new Console($this->app))
            ->resolveCommands($this->commands);
    }

    /**
     * Resolve the CLI command and return the output.
     *
     * @param ArgvInput $input
     * @param ConsoleOutput $output
     * @return int|ConsoleOutput
     * @throws Exception
     */
    public function run(ArgvInput $input, ConsoleOutput $output): int|ConsoleOutput
    {
        try {
            // Bootstrap the system.
            $this->bootstrapSystem();

            // Dispatch the twipe command resolver.
            return $this->getTwipsiConsole()
                ->run($input, $output);

        } catch (Throwable $e) {

            // If we have a custom exception handler attached, then
            // collect and handle exceptions with the custom one.
            $this->handleException($output, $e);
        }

        return 1;
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

        $this->loadCommands();
    }

    protected function loadCommands(): void
    {

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
     * Terminate any terminate.
     *
     * @param ArgvInput $input
     * @param int $status
     * @return void
     */
    public function terminate(ArgvInput $input, int $status): void
    {
    }
}
