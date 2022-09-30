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
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class Console extends SymfonyApplication
{
    /**
     * The twipsi application.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Construct Console.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct('Twipsi Framework', $app->version());

        $this->app = $app;
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
    }

    /**
     * Run the console.
     *
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     * @throws Exception
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        return parent::run($input, $output);
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
}
