<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twipsi\Foundation\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    protected static $defaultName = 'test';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(InputInterface $input, OutputInterface $output): void
    {
        $message = 'Yeah BABY!!!';

        (new SymfonyStyle($input, $output))->write($message, false, 0);
    }
}