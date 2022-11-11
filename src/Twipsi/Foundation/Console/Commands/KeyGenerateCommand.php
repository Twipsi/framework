<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Console\Command;
use Twipsi\Support\KeyGenerator;

#[AsCommand(name: 'key:generate')]
class KeyGenerateCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'key:generate';

    /**
     * Command options.
     *
     * @var array|array[]
     */
    protected array $options = [
        ['show', null, InputOption::VALUE_NONE, 'Show the generated system key'],
        ['strict', null, InputOption::VALUE_NONE, 'Overwrite the existing key if found'],
    ];

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Generate and set the application key';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileException
     */
    public function handle(): void
    {
        try {
            $env = new FileItem($this->app->environmentFile());

        } catch (FileNotFoundException) {
            $this->render->error('No environment file found.');

            return;
        }

        $this->option('strict')
            ? $this->saveKey($env, $key = $this->generateKey(), true)
            : $this->saveKey($env, $key = $this->generateKey());

        // Show the key if option is set.
        if($this->option('show')) {
            $this->comment($key);
        }

        // Set the key to application configuration.
        $this->app->get('config')->set('security.app_key', $key);

        $this->render->success('A system key was successfully generated');
    }

    /**
     * Generate a 32 bit key.
     *
     * @return string
     */
    protected function generateKey(): string
    {
        return 'BS64:'.base64_encode(
            KeyGenerator::generateSystemKey(32)
        );
    }

    /**
     * Save the system key to environment file.
     *
     * @param FileItem $file
     * @param string $key
     * @param bool $strict
     * @return void
     * @throws FileException
     */
    protected function saveKey(FileItem $file, string $key, bool $strict = false): void
    {
        $current = $this->app->get('config')->get('security.app_key');

        // If we already have a key, and we don't want to overwrite, don't replace it.
        if(strlen($current) !== 0 && ! $strict) {
            return;
        }

        $this->updateEnvFile($file, $key, $current);
    }

    /**
     * Update the environment file.
     *
     * @param FileItem $file
     * @param string $key
     * @param string $current
     * @return void
     * @throws FileException
     */
    protected function updateEnvFile(FileItem $file, string $key, string $current): void
    {
        if(empty($current)) {
            $escaped = preg_quote('=', '/');
        } else {
            $escaped = preg_quote('=BS64:'.base64_encode($current), '/');
        }

        $line = "/^APP_KEY{$escaped}/m";
        $file->put(preg_replace($line, 'APP_KEY='.$key, $file->getContent()));
    }
}