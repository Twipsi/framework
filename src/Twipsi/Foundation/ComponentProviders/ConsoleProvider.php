<?php

namespace Twipsi\Foundation\ComponentProviders;

use Twipsi\Components\Session\SessionSubscriber;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;
use Twipsi\Foundation\Console\Commands\CacheClearCommand;
use Twipsi\Foundation\Console\Commands\ConfigCacheCommand;
use Twipsi\Foundation\Console\Commands\ConfigClearCommand;
use Twipsi\Foundation\Console\Commands\EventCacheCommand;
use Twipsi\Foundation\Console\Commands\EventClearCommand;
use Twipsi\Foundation\Console\Commands\EventListCommand;
use Twipsi\Foundation\Console\Commands\KeyGenerateCommand;
use Twipsi\Foundation\Console\Commands\RouteCacheCommand;
use Twipsi\Foundation\Console\Commands\RouteClearCommand;
use Twipsi\Foundation\Console\Commands\RouteListCommand;
use Twipsi\Foundation\Console\Commands\TestCommand;
use Twipsi\Foundation\Console\Commands\ViewCacheCommand;
use Twipsi\Foundation\Console\Commands\ViewClearCommand;
use Twipsi\Foundation\Console\Console;

class ConsoleProvider extends ComponentProvider implements DeferredComponentProvider
{
    /**
     * List of commands to register.
     *
     * @var array|string[]
     */
    protected array $commands = [
        'CacheClear' => CacheClearCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'EventCache' => EventCacheCommand::class,
        'EventClear' => EventClearCommand::class,
        'EventList' => EventListCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
        'RouteCache' => RouteCacheCommand::class,
        'RouteClear' => RouteClearCommand::class,
        'RouteList' => RouteListCommand::class,
        'ViewCache' => ViewCacheCommand::class,
        'ViewClear' => ViewClearCommand::class,
        'TestCommand' => TestCommand::class,
    ];

    /**
     * Register service provider.
     *
     * @return void
     */
    public function register(): void
    {
        var_dump('REGISTERING PROVIDER');

        // Bind the session handler to the application.
//        $this->app->keep('console.app', function (Application $app) {
//            return new SessionSubscriber($app->config, $app->encrypter);
//        });

        foreach ($this->commands as $name => $command) {
            if(method_exists($this, $method = "register{$name}Command")) {
                $this->{$method}();
            } else {
                $this->app->keep($command);
            }
        }

        $this->loadCommands($this->commands);
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return array_values($this->commands);
    }

    /**
     * Load the commands in the console.
     *
     * @param array $commands
     * @return void
     */
    protected function loadCommands(array $commands): void
    {
        var_dump('LOADING COMMANDS');

        Console::boot(function($console) use ($commands) {
            $console->resolveCommands($commands);
        });
    }
}