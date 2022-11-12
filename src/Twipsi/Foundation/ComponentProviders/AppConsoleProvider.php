<?php

namespace Twipsi\Foundation\ComponentProviders;

use ReflectionClass;
use ReflectionException;
use Twipsi\Components\File\DirectoryManager;
use Twipsi\Foundation\ComponentProvider;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Console\CommandSchedule;
use Twipsi\Foundation\Console\Console;

abstract class AppConsoleProvider extends ComponentProvider implements DeferredComponentProvider
{
    /**
     * List of commands to register.
     *
     * @var array|string[]
     */
    protected array $commands = [];

    /**
     * Set the schedule for any commands.
     *
     * @param CommandSchedule $schedule
     * @return void
     */
    protected function schedule(CommandSchedule $schedule): void
    {
        // @Bridge
    }

    /**
     * Register service provider.
     *
     * @return void
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->app->keep('console.schedule', function() {
            $this->schedule(new CommandSchedule());
        });

        $this->loadCommands(array_values($this->commands));
    }

    /**
     * Load the commands in the console.
     *
     * @param array $commands
     * @return void
     * @throws ReflectionException
     */
    protected function loadCommands(array $commands): void
    {
        if(!empty($commands)) {
            Console::boot(function($command) use ($commands) {
                $command->resolveCommands($commands);
            });
        }

        $this->read(__DIR__.'/Commands');
    }

    /**
     * Load the commands from the application commands folder.
     *
     * @param string $path
     * @return void
     * @throws ReflectionException
     */
    protected function read(string $path): void
    {
        $commands = (new DirectoryManager)->list($path);

        if(empty($commands)) {
            return;
        }

        $commands = array_map(function($command) {
            $parts = explode(DIRECTORY_SEPARATOR, $command);
            return end($parts);
        }, $commands);

        foreach($commands as $command) {

            $class = ' \App\Console\Commands\\'
                .str_replace('.php', '', $command);

            if(is_subclass_of($class, Command::class)
                && !(new ReflectionClass($class))->isAbstract()) {

                Console::boot(function($console) use($command) {
                    $console->resolve($command);
                });
            }
        }
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['console.schedule'];
    }
}