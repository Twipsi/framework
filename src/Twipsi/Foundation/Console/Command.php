<?php

namespace Twipsi\Foundation\Console;

use ReflectionException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Console\Renderer\RenderFactory;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

abstract class Command extends SymfonyCommand
{
    use HandlesCalls, HandlesSymfonyOutput, HandlesIO;

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
     * Command arguments.
     *
     * @var array
     */
    protected array $arguments = [];

    /**
     * Command options.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Construct the command.
     */
    public function __construct()
    {
        parent::__construct($this->name);

        $this->setDescription($this->description ?? '');
        $this->setHelp($this->help ?? '');
        $this->setHidden($this->hidden);

        $this->registerArguments();
        $this->registerOptions();
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
        $this->render = new RenderFactory(
            $this->output = new SymfonyStyle($input, $output)
        );

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
        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        return (int) $this->app->call($this, $method);
    }

    /**
     * Register the command options.
     *
     * @return void
     */
    protected function registerOptions(): void
    {
        foreach ($this->options as $option) {
            if ($option instanceof InputOption) {
                $this->getDefinition()->addOption($option);

            } else {
                $this->addOption(...$option);
            }
        }
    }

    /**
     * Register the command arguments.
     *
     * @return void
     */
    protected function registerArguments(): void
    {
        foreach($this->arguments as $argument) {
            if ($argument instanceof InputArgument) {
                $this->getDefinition()->addArgument($argument);

            } else {
                $this->addArgument(...$argument);
            }
        }
    }

    /**
     * Check if a command has an option.
     *
     * @param string $option
     * @return bool
     */
    protected function hasOption(string $option): bool
    {
        return $this->input->hasOption($option);
    }

    /**
     * Get the value of an option.
     *
     * @param string $option
     * @return mixed
     */
    protected function option(string $option): mixed
    {
        return $this->input->getOption($option);
    }

    /**
     * Get the value of all options.
     *
     * @return array
     */
    protected function options(): array
    {
        return $this->input->getOptions();
    }

    /**
     * Check if a command has an argument.
     *
     * @param string $argument
     * @return bool
     */
    protected function hasArgument(string $argument): bool
    {
        return $this->input->hasOption($argument);
    }

    /**
     * Get the value of an argument.
     *
     * @param string $argument
     * @return mixed
     */
    protected function argument(string $argument): mixed
    {
        return $this->input->getArgument($argument);
    }

    /**
     * Get the value of all arguments.
     *
     * @return array
     */
    protected function arguments(): array
    {
        return $this->input->getArguments();
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