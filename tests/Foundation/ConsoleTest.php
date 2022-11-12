<?php

namespace Twipsi\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Console\Console;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Tests\Foundation\Fakes\FakeCommand;

class ConsoleTest extends TestCase
{
    /**
     * The Console object.
     *
     * @var Console
     */
    protected Console $console;

    /**
     * The buffered output.
     *
     * @var BufferedOutput
     */
    protected BufferedOutput $output;

    /**
     * Setup test environment.
     *
     * @return void
     * @throws ApplicationManagerException
     */
    protected function setUp(): void
    {
        $app = new Application();
        $this->console = new Console($app, $app->version());
        $this->console->resolve(FakeCommand::class);

        $this->output = new \Symfony\Component\Console\Output\BufferedOutput;
    }

    public function testConsoleShouldHandleCommandFromCLI()
    {
        $input = new ArgvInput(['cli.php', 'test']);

        $this->console->run($input, $this->output);

        $this->assertSame(trim($this->console->lastOutput()), 'Fake Command Executed.');
    }

    public function testConsoleShouldThrowExceptionIfNotFound()
    {
        $input = new ArgvInput(['cli.php', 'fake']);

        $this->expectException(CommandNotFoundException::class);
        $this->console->run($input, $this->output);
    }

    public function testConsoleShouldHandleCommandFromName()
    {
        $this->console->call('test', [], $this->output);

        $this->assertSame(trim($this->output->fetch()), 'Fake Command Executed.');
    }

    public function testCommandShouldBeAbleToCallAnotherCommandByName()
    {
        $mock = $this->createMock(\Twipsi\Tests\Foundation\Fakes\CallableCommand::class);

        $mock->expects($this->any())
            ->method('getName')
            ->willReturn('callable');

        $mock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $mock->expects($this->once())
            ->method('setTwipsi');

        $mock->expects($this->once())
            ->method('run')
            ->willReturn(1);

        $this->console->add($mock);
        $this->console->call('test --name', [], $this->output);

        $this->assertSame(trim($this->output->fetch()), 'Fake Command Executed.');
    }

    public function testCommandShouldBeAbleToCallAnotherCommand()
    {
        $this->console->call('test --call', [], $this->output);

        $output = preg_replace('/\s+/', ' ', $this->output->fetch());

        $this->assertSame(trim($output),
            'This should not be outputed.'.' Fake Command Executed.');
    }

    public function testCommandShouldBeAbleToCallAnotherCommandSilently()
    {
        $this->console->call('test --silent', [], $this->output);

        $this->assertSame(trim($this->output->fetch()), 'Fake Command Executed.');
    }

    public function testCommandShouldBeDIByApplication()
    {
        $command = new \Twipsi\Tests\Foundation\Fakes\CallableCommand();
        $this->console->add($command);

        $this->console->call('callable --di');

        $this->assertSame(trim($this->console->lastOutput()), 'DI is working.');
    }

    public function testCommandShouldHandleArguments()
    {
        $this->console->call('test --op1=hello aaa', [], $this->output);

        $output = preg_replace('/\s+/', ' ', $this->output->fetch());

        $this->assertSame(trim($output),
            'aaa.'.' Fake Command Executed.');
    }

    public function testCommandShouldHandleArgumentsWhenSendingParameters()
    {
        $this->console->call('test', ['command' => 'test', 'arg1' => 'aaa', '--op1' => 'hello'], $this->output);

        $output = preg_replace('/\s+/', ' ', $this->output->fetch());

        $this->assertSame(trim($output),
            'aaa.'.' Fake Command Executed.');
    }
}