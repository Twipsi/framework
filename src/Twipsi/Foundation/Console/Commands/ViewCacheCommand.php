<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileBag;
use Twipsi\Components\File\FileItem;
use Twipsi\Components\View\ViewFactory;
use Twipsi\Foundation\Console\Command;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Bags\ArrayBag;
use Twipsi\Support\Str;

#[AsCommand(name: 'views:cache')]
class ViewCacheCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'views:cache';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Cache all the view files';

    /**
     * Handle the command.
     *
     * @return void
     * @throws FileException
     * @throws ExceptionInterface
     * @throws ApplicationManagerException
     */
    public function handle(): void
    {
        // Flush the previous cache.
        $this->silent('views:clear');

        $repository = $this->discoverViewFiles(
            $path = $this->app->get('view.locator')->getPath()
        );

        if($repository->empty()) {
            $this->render->error(
                sprintf("No view files in directory [%s] could not be found", $path)
            );

            return;
        }

        $factory = $this->app->get('view.factory');

        // Cache the view files.
        $repository->loop(function($file) use ($factory) {
            $this->saveCache($file, $factory);
        });

        $this->render->success('The views have been successfully cached');
    }

    /**
     * Discover view files.
     *
     * @param string $where
     * @return ArrayBag
     */
    protected function discoverViewFiles(string $where): ArrayBag
    {
        return ArrayBag::collect(
            (new FileBag($where, '.php'))->listAbsolute()
        )->filter(function($file) {
            return Str::hay($file)->resembles('.axis');
        });
    }

    /**
     * Save the cache to file.
     *
     * @param string $file
     * @param ViewFactory $factory
     * @return void
     * @throws FileNotFoundException
     * @throws NotSupportedException
     */
    protected function saveCache(string $file, ViewFactory $factory): void
    {
        $extractor = $factory->getExtractor('.axis.php');

        $extractor->compileView(new FileItem($file));
    }
}