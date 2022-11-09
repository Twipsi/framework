<?php

namespace Twipsi\Tests\Foundation\Fakes;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'callable')]
class CallableCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'callable';

    /**
     * Command options.
     *
     * @var array|array[]
     */
    protected array $options = [
        ['di', null, InputOption::VALUE_NONE, 'Test dependency inject'],
        ['vv', null, InputOption::VALUE_NONE, 'Test dependency inject'],
    ];

    /**
     * Command arguments.
     *
     * @var array|array[]
     */
    protected array $arguments = [
        ['arg1', InputArgument::OPTIONAL, 'Test command arguments', 'novalue'],
    ];

    /**
     * Handle the command.
     *
     * @param HttpRequest $request
     * @return void
     */
    public function handle(HttpRequest $request): void
    {
        if($this->option('vv')) {
            $this->plain($this->argument('arg1'));
        }
        else if($this->option('di')) {
            $this->plain('DI is working.');
        } else {
            $this->plain('This should not be outputed.');
        }
    }
}