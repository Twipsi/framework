<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

#[AsCommand(name: 'boot:cache')]
class BootCacheCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'boot:cache';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Cache all the bootstrappers';

    /**
     * Handle the command.
     *
     * @return void
     * @throws ExceptionInterface
     * @throws ApplicationManagerException
     */
    public function handle(): void
    {
        $this->silent('components:cache');
        $this->silent('config:cache');
        $this->silent('events:cache');
        $this->silent('routes:cache');

        $this->render->success('The bootstrappers have been successfully cached');
    }
}