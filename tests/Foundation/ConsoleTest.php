<?php

namespace Twipsi\Tests\Foundation;

use Twipsi\Foundation\Application\Application;
use \Symfony\Component\Console\Input\StringInput;

class ConsoleTest
{
    protected Application $app;
    
    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->keep('console', \Twipsi\Foundation\Console\Kernel::class);
    }

    public function testGeneral()
    {
        $input = new \Symfony\Component\Console\Input\ArgvInput;
        $output = new \Symfony\Component\Console\Output\ConsoleOutput;

        $status = $app->console->run($input, $output);
    }
}