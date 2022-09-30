<?php

namespace Twipsi\Foundation\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twipsi\Foundation\Application\Application;

class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * The twipsi application.
     *
     * @var Application
     */
    protected Application $app;

    public function setTwipsi(Application $app): void
    {
        $this->app = $app;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        return (int) $this->{$method}($input, $output);
    }
}