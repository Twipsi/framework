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

namespace Twipsi\Components\File;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Support\Str;

class FileBag implements IteratorAggregate, Countable
{
    /**
     * Continer for files found under the path.
     *
     * @var array
     */
    protected array $files = [];

    /**
     * The path to find files.
     *
     * @var string
     */
    protected string $location;

    /**
     * Construct the file storage based on a directory.
     *
     * @param string $location
     * @param null|string $extension
     */
    public function __construct(string $location, ?string $extension = null)
    {
        // Get the iterator for the firectory and sub folders.
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->location = $this->parsePath($location) . DIRECTORY_SEPARATOR
            )
        );

        foreach ($iterator as $file) {

            if ($file->isDir() || (!is_null($extension) &&
                $file->getExtension() !== str_replace('.', '', $extension))) {

                continue;
            }


            $this->files[] = ltrim(str_replace(
                $this->location, '', $this->parsePath($file->getPathname())
            ), DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Parse the path string changing seperators to system ones.
     *
     * @param string $path
     *
     * @return string
     */
    protected function parsePath(string $path): string
    {
        return rtrim(
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path),
            DIRECTORY_SEPARATOR);
    }

    protected function parseFile(string $path): string
    {
        return ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Return array of files if called.
     *
     * @return array
     */
    public function __invoke(): array
    {
        return $this->list();
    }

    /**
     * Return all the file names in file array using exceptions.
     *
     * @param string ...$exceptions
     *
     * @return array
     */
    public function list(string ...$exceptions): array
    {
        if (!func_get_args()) {
            return $this->files;
        }

        $filteredParameters = array_filter($this->files, function ($k) use ($exceptions) {
            return !in_array($this->parseFile($k), $exceptions);
        }, ARRAY_FILTER_USE_KEY);

        return $filteredParameters;
    }

    /**
     * Return all the absolute file names in file array using exceptions.
     *
     * @param string ...$exceptions
     *
     * @return array
     */
    public function listAbsolute(string ...$exceptions): array
    {
        foreach($this->list(...$exceptions) as $file ) {
            $abs[] = $this->location.$this->parseFile($file);
        }

        return $abs ?? [];
    }

    /**
     * Replace some content in the file.
     *
     * @param string $what
     * @param string $with
     * @param string $file
     *
     * @return FileBag
     *
     * @throws FileException
     */
    public function replace(string $what, string $with, string $file): FileBag
    {
      $file = $this->parseFile($file);

        try {
            $content = $this->get($file);
            $this->put($file, str_replace($what, $with, $content));

        } catch (FileException) {
            throw new FileException("Could not replace content in file [$this->location.$file]");
        }

        return $this;
    }

    /**
     * Retrieve the content from a file.
     *
     * @param string $file
     * @param mixed|null $default
     *
     * @return string
     *
     * @throws FileException
     */
    public function get(string $file, mixed $default = null): string
    {
      $file = $this->parseFile($file);

        if (!$this->has($file) && null !== $default) {
            return $default;
        }

        if (!$content = @file_get_contents($this->location . $file)) {
            throw new FileException("Could not retrieve content for file [$this->location.$file]");
        }

        return $content;
    }

    /**
     * Check if file exists in array and physically.
     *
     * @param string $file
     *
     * @return bool
     */
    public function has(string $file): bool
    {
      $file = $this->parseFile($file);

        if (!in_array($file, $this->files)) {
            return false;
        }

        return is_file($this->location . $file);
    }

    /**
     * Set content of a file, overiting any content inside or create it.
     *
     * @param string $file
     * @param mixed $value
     * @param int $mode
     *
     * @return FileBag
     *
     * @throws FileException
     */
    public function put(string $file, mixed $value, int $mode = 0777): FileBag
    {
      $file = $this->parseFile($file);

        if (!is_dir($dir = dirname($this->location . $file))) {

            (new DirectoryManager())->make($dir, $mode, true);
        }

        if (!@file_put_contents($this->location . $file, $value)) {
            throw new FileException(sprintf("Could not create or write to file %s", $this->location . $file));
        }

        if (!$this->has($file)) {
            $this->files[] = $file;
        }

        return $this;
    }

    /**
     * Add content to the end of the file.
     *
     * @param string $file
     * @param mixed $value
     *
     * @return FileBag
     *
     * @throws FileException
     */
    public function push(string $file, mixed $value): FileBag
    {
      $file = $this->parseFile($file);

        if (!@file_put_contents($this->location . $file, $value, FILE_APPEND)) {
            throw new FileException("Could not push to file [$this->location.$file]");
        }

        return $this;
    }

    /**
     * Add Content to the beggining of a file.
     *
     * @param string $file
     * @param mixed $value
     *
     * @return FileBag
     *
     * @throws FileException
     */
    public function prepend(string $file, mixed $value): FileBag
    {
      $file = $this->parseFile($file);

        try {
            $content = $this->get($file);
            $this->put($file, $value . $content);

        } catch (FileException) {

            throw new FileException("Could not prepend content to file [$this->location.$file]");
        }

        return $this;
    }

    /**
     * Check if file contains any substring.
     *
     * @param string $file
     * @param mixed $value
     *
     * @return bool
     *
     * @throws FileException
     */
    public function contains(string $file, mixed $value): bool
    {
      $file = $this->parseFile($file);

        try {
            $content = $this->get($file);
            return false !== strpos($content, $value);

        } catch (FileException) {

            throw new FileException("Could not read content from file [$this->location.$file]");
        }
    }

    /**
     * Set or get CHMOD for file.
     *
     * @param string $file
     * @param int|null $mode
     *
     * @return string
     *
     * @throws FileException
     */
    public function chmod(string $file, ?int $mode = null): string|FileBag
    {
      $file = $this->parseFile($file);

        if (is_null($mode) && $this->has($file)) {
            return substr(sprintf('%o', fileperms($this->location . $file)), -4);
        }

        if ($this->has($file) && chmod($this->location . $file, $mode)) {
            return $this;
        }

        throw new FileException("Could not set CHMOD for file [$this->location.$file]");
    }

    /**
     * Move a file to another destination.
     *
     * @param string $file
     * @param string $location
     *
     * @return FileBag
     *
     * @throws FileException
     */
    public function move(string $file, string $location): FileBag
    {
      $file = $this->parseFile($file);

        if ($this->has($file) && rename($this->location . $file, $location)) {
            return $this;
        }

        throw new FileException("Could not move file [$this->location.$file]");
    }

    /**
     * Copy a file to another destination.
     *
     * @param string $file
     * @param string $location
     *
     * @return FileBag
     *
     * @throws FileException
     */
    public function copy(string $file, string $location): FileBag
    {
      $file = $this->parseFile($file);

        if ($this->has($file) && copy($this->location . $file, $location)) {
            return $this;
        }

        throw new FileException("Could not copy file [$this->location.$file]");
    }

    /**
     * Get last modified date.
     *
     * @param string $file
     *
     * @return int
     *
     * @throws FileException
     */
    public function modified(string $file): int|bool
    {
      $file = $this->parseFile($file);

        if ($this->has($file) && $time = filemtime($this->location . $file)) {
            return $time;
        }

        throw new FileException("Could not get last modified date for file [$this->location.$file]");
    }

    /**
     * Include all the files adding filename as key.
     *
     * @return mixed
     */
    public function includeAll(): mixed
    {
        $files = $this->list();
        $location  = $this->location;

        return (static function() use ($files, $location) {

            foreach($files as $file) {
                $section = Str::hay(mb_strtolower(basename($file)))->before('.');
                $required[$section] = require $location . $file;
            }

            return $required ?? [];
          })();
    }

    /**
     * Delete all the files in the bag.
     *
     * @return FileBag
     */
    public function flush(): FileBag
    {
        (new DirectoryManager)->delete($this->location);

        return $this;
    }

    /**
     * Remove a file from files and delete it physically.
     *
     * @param string $file
     *
     * @return FileBag
     */
    public function delete(string $file): FileBag
    {
      $file = $this->parseFile($file);

        unset($this->files[$file]);
        @unlink($this->location . $file);

        return $this;
    }

    /**
     * Returns an iterator for files.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->files);
    }

    /**
     * Returns the number of files.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->files);
    }

}
