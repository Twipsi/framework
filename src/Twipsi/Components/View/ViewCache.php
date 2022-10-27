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

namespace Twipsi\Components\View;

use Twipsi\Components\File\DirectoryManager;
use Twipsi\Components\File\FileBag as FileFactory;
use Twipsi\Components\File\FileItem;
use InvalidArgumentException;

class ViewCache
{
    /**
     * File storage container.
     */
    protected FileFactory $files;

    /**
     * Path to view cache.
     */
    protected string $cachePath;

    /**
     * The extension we should compile to.
     */
    protected string $compileTo = "php";

    /**
     * View cache constructor
     */
    public function __construct(string $cachePath, protected bool $useCache, string $compileTo = null)
    {
        if (empty($cachePath)) {
            throw new InvalidArgumentException(
                "Please provide a path to cache views."
            );
        }

        $this->cachePath = rtrim($cachePath, "/");

        if (!is_null($compileTo)) {
            $this->compileTo = $compileTo;
        }

        $this->files = new FileFactory($this->cachePath);
    }

    /**
     * Store the view cache.
     */
    public function store(FileItem $file, string $content): void
    {
        if (!$this->useCache) {
            return;
        }

        $this->buildCacheDirectory($this->cachePath);

        $cacheFile = $this->getCacheFileName($file);

        $this->files->put($cacheFile, $content);
    }

    /**
     * Check if a view is expired in cache.
     */
    public function expired(FileItem $file): bool
    {
        if (!$this->useCache) {
            return true;
        }

        if (!$this->files->has($name = $this->getCacheFileName($file))) {
            return true;
        }

        return $file->modified() >= $this->files->modified($name);
    }

    /**
     * Check if a view is expired in cache.
     */
    public function getCompiledViewFile(FileItem $file): FileItem
    {
        return new FileItem(
            $this->cachePath . "/" . $this->getCacheFileName($file)
        );
    }

    /**
     * Get the cachable file name.
     */
    public function getCacheFileName(FileItem $file): string
    {
        return sha1("v2" . $file->getPath()) . "." . $this->compileTo;
    }

    /**
     * Check if a view is expired in cache.
     */
    public function buildCacheDirectory(string $path): void
    {
        if (!is_dir($path)) {
            (new DirectoryManager())->make($path);
        }
    }

    /**
     * Check if a view should be cached.
     */
    public function usesCache(): bool
    {
        return $this->useCache;
    }
}
