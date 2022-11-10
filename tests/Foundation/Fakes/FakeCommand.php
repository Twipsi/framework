<?php

namespace Twipsi\Tests\Foundation\Fakes;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

#[AsCommand(name: 'test')]
class FakeCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'test';

    /**
     * Command options.
     *
     * @var array|array[]
     */
    protected array $options = [
        ['name', null, InputOption::VALUE_NONE, 'Test call another command'],
        ['call', null, InputOption::VALUE_NONE, 'Test call another command'],
        ['silent', null, InputOption::VALUE_NONE, 'Test call another command'],
        ['op1', null, InputOption::VALUE_OPTIONAL, 'Test call another command', false],
    ];

    protected array $arguments = [
        ['arg1', InputArgument::OPTIONAL, 'Test command arguments', 'novalue'],
    ];

    /**
     * Handle the command.
     *
     * @return void
     * @throws ExceptionInterface
     * @throws ApplicationManagerException
     */
    public function handle(): void
    {
        if($this->option('name')) {
            $this->call('callable');
        }

        if($this->option('call')) {
            $this->call(\Twipsi\Tests\Foundation\Fakes\CallableCommand::class);
        }

        if($this->option('silent')) {
            $this->silent(\Twipsi\Tests\Foundation\Fakes\CallableCommand::class);
        }

        if($this->option('op1') === 'hello') {
            $this->call(\Twipsi\Tests\Foundation\Fakes\CallableCommand::class,
            ['--vv' => 'yolo', 'arg1' => $this->argument('arg1')]);
        }

        $this->render->debug('Fake Command Executed');
    }
}