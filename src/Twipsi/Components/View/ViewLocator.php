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

use InvalidArgumentException;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Support\Str;

class ViewLocator
{
    /**
     * Found views container.
     */
    protected array $found;

    /**
     * Loaded route collection
     */
    protected array $extensions = ["axis.php", "php", "html", "css"];

    /**
     * View finder constructor
     */
    public function __construct(protected string $path, protected string $theme, array $extensions = null)
    {
        if (!is_null($extensions)) {
            $this->extensions = $extensions;
        }
    }

    /**
     * Attempt to find the location of a view.
     */
    public function locate(string $name): ?FileItem
    {
        // If we have already located it return it.
        if (isset($this->found[$name])) {
            return $this->found[$name];
        }

        if ($file = $this->attemptToFindFile($name, $this->path)) {
            return $this->found[$name] = $file;
        }

        throw new InvalidArgumentException(
            sprintf("View [%s] could not be found.", $name)
        );
    }

    /**
     * Attempt to find the location of a view.
     */
    public function attemptToFindFile(string $name, string $path): ?FileItem
    {
        $directory = $this->buildBaseViewDirectory($path);

        foreach ($this->getNamePossibilities($name) as $file) {
            try {
                return new FileItem($directory . $file);
            } catch (FileNotFoundException) {
                continue;
            }
        }

        return null;
    }

    /**
     * Convert the view name to a path string.
     */
    protected function buildBaseViewDirectory(string $path): string
    {
        if (is_dir($fullPath = rtrim($path, "/") . "/" . $this->theme)) {
            return $fullPath;
        }

        throw new InvalidArgumentException(
            sprintf(
                "Theme [%s] could not be found in directory [%s].",
                $this->theme,
                $path
            )
        );
    }

    /**
     * Convert the view name to a path string.
     */
    protected function getNamePossibilities(string $name): array
    {
        $path = $this->convertToPath($name);

        return array_map(
            fn($extension) => $path . "." . $extension,
            $this->extensions
        );
    }

    /**
     * Convert the view name to a path string.
     */
    protected function convertToPath(string $name): string
    {
        if (!Str::hay($name)->contains(".")) {
            return "/" . trim($name, "/");
        }

        return "/" . implode("/", explode(".", $name));
    }

    /**
     * Set the current theme for the finder.
     */
    public function theme(string $theme): ViewLocator
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get the current theme for the finder.
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Set the current view path for the finder.
     */
    public function path(string $path): ViewLocator
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the current view path for the finder.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Flush the found files.
     *
     * @return void
     */
    public function flushLocator(): void
    {
        $this->found = [];
    }
}
