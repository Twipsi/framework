<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

#[AsCommand(name: 'boot:clear')]
class BootClearCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'boot:clear';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Delete all boot cache file';

    /**
     * Handle the command.
     *
     * @return void
     * @throws ExceptionInterface
     * @throws ApplicationManagerException
     */
    public function handle(): void
    {
        $this->silent('components:clear');
        $this->silent('config:clear');
        $this->silent('events:clear');
        $this->silent('routes:clear');

        $this->render->success('The boot cache has been deleted');
    }
}