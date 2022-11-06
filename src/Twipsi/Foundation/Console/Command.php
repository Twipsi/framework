<?php

namespace Twipsi\Foundation\Console;

use ReflectionException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

abstract class Command extends SymfonyCommand
{
    /**
     * The twipsi application.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name;

    /**
     * The description of the command.
     *
     * @var string
     */
    protected string $description;

    /**
     * The console help of the command.
     *
     * @var string
     */
    protected string $help;

    /**
     * If command should be shown in the console list.
     *
     * @var bool
     */
    protected bool $hidden = false;

    /**
     * Construct the command.
     */
    public function __construct()
    {
        parent::__construct($this->name);

        $this->setDescription($this->description ?? '');
        $this->setHelp($this->help ?? '');
        $this->setHidden($this->hidden);
    }

    /**
     * Run the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionInterface
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        var_dump('RUNNING');
        $this->output = new SymfonyStyle($input, $output);

        return parent::run(
            $this->input = $input, $this->output
        );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ReflectionException
     * @throws ApplicationManagerException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        var_dump('EXECUTING');
        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        return (int) $this->app->call($this, $method);
    }

    /**
     * Set the twipsi application.
     *
     * @param Application $app
     * @return void
     */
    public function setTwipsi(Application $app): void
    {
        $this->app = $app;
    }
}