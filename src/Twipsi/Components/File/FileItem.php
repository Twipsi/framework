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

use Twipsi\Components\File\MimeType;
use Twipsi\Support\Str;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\Exceptions\FileException;

class FileItem
{
  /**
  * Path to the file.
  */
  protected string $path;

  /**
  * Construct our file item based on location.
  */
  public function __construct(string $path)
  {
    if (! is_file($path)) {
      throw new FileNotFoundException(sprintf("File [%s] not found", $path));
    }

    $this->path = $path;
  }

  /**
  * Replace some content in the file.
  *
  * @throws FileException
  */
  public function replace(string $what, string $with) : FileItem
  {
    try {
      $content = $this->getContent();
      $this->put(str_replace($what, $with, $content));

    } catch (FileException $e) {
      throw new FileException(sprintf("Could not replace content in file [%s]", $this->getPath()));
    }

    return $this;
  }

  /**
  * Set content of a file, overiting any content inside or create it.
  *
  * @throws FileException
  */
  public function put(mixed $value) : FileItem
  {
    if (! @file_put_contents($this->getPath(), $value)) {
      throw new FileException(sprintf("Could not create or write to file [%s]", $this->getPath()));
    }

    return $this;
  }

  /**
  * Add content to the end of the file.
  *
  * @throws FileException
  */
  public function push(mixed $value) : FileItem
  {
    if (! @file_put_contents($this->getPath(), $value, FILE_APPEND)) {
      throw new FileException(sprintf("Could not push to file [%s]", $this->getPath()));
    }

    return $this;
  }

  /**
  * Add Content to the beggining of a file.
  *
  * @throws FileException
  */
  public function prepend(mixed $value) : FileItem
  {
    try {
      $content = $this->getContent();
      $this->put($value.$content);

    } catch(FileException $e) {

      throw new FileException(sprintf("Could not prepend content to file [%s]", $this->getPath()));
    }

    return $this;
  }

  /**
  * Retrieve the content from a file.
  *
  * @throws FileException
  */
  public function getContent(mixed $default = null) : string
  {
    if (! $content = @file_get_contents($this->getPath())) {

      if (null !== $default) {
        return $default;
      }

      throw new FileException(sprintf("Could not retrieve content for file [%s]", $this->getPath()));
    }

    return $content;
  }

  public function getLines(): array
  {
      return file($this->getPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  }

  /**
  * Extract the content from a file using data array.
  */
  public function extractContent(array $data = []) : mixed
  {
    $path = $this->getPath();

    return (static function() use ($path, $data) {
      extract($data, EXTR_SKIP);

      return require $path;
    })();
  }

  public function require() : mixed
  {
    $path = $this->getPath();

    return (static function() use ($path) {
      return require $path;
    })();
  }

  public function include() : mixed
  {
    $path = $this->getPath();

    return (static function() use ($path) {
      return include $path;
    })();
  }

  /**
  * Check if file contains any substring.
  *
  * @throws FileException
  */
  public function contains(mixed $value) : bool
  {
    try {
      $content = $this->getContent();
      return false !== \strpos($content,$value);

    } catch(FileException $e) {

      throw new FileException(sprintf("Could not read content from file [%s]", $this->getPath()));
    }
  }

  /**
  * Remove a file from files and delete it physically.
  */
  public function delete() : FileItem
  {
    @unlink($this->getPath());

    return $this;
  }

  /**
  * Set CHMOD for file.
  *
  * @throws FileException
  */
  public function chmod(int $mode) : FileItem
  {
    if (chmod($this->getPath(), $mode)) {
      return $this;
    }

    throw new FileException(sprintf("Could not set CHMOD for file [%s]", $this->getPath()));
  }

  /**
  * Move a file to another destination.
  *
  * @throws FileException
  */
  public function move(string $location, string $name = null) : FileItem
  {
    $target = $this->prepareTargetFile($location, $name);

    if (rename($this->getPath(), $target)) {
      @chmod($target, 666);

      return new self($target);
    }

    throw new FileException(sprintf("Could not move file [%s] to location [%s]", $this->getPath(), $target));
  }

  /**
  * Copy a file to another destination.
  *
  * @throws FileException
  */
  public function copy(string $location, string $name = null) : FileItem
  {
    $target = $this->prepareTargetFile($location, $name);

    if (copy($this->getPath(), $target)) {
      @chmod($target, 666);

      return new self($target);
    }

    throw new FileException(sprintf("Could not copy file [%s] to location [%s]", $this->getPath(), $target));
  }

  /**
  * Get last modified date
  *
  * @throws FileException
  */
  public function modified() : int|bool
  {
    if ($time = filemtime($this->getPath())) {
      return $time;
    }

    throw new FileException(sprintf("Could not get last modified date for file [%s]", $this->getPath()));
  }

  /**
  * Prepare the directory and file before moving.
  *
  * @throws FileException
  */
  protected function prepareTargetFile(string $location, string $name = null) : string
  {
    $location = rtrim($location, '/\\');

    if (! is_dir($location)) {

      if(! mkdir($location, 0777, true) && !is_dir($location)) {
        throw new FileException(sprintf("Directory [%s] could not be created", $location));
      }
    }

    if (! is_writable($location)) {
      throw new FileException(sprintf("Directory [%s] is not writable", $location));
    }

    return $location.DIRECTORY_SEPARATOR.($name = null !== $name ? $this->getBaseName($name) : $this->getBaseName());
  }

  /**
  * Get the actual filename from the path.
  */
  public function getBaseName(string $file = null) : string
  {
    $file = null !== $file ? $file : $this->getPath();

    if (Str::hay($file)->has('/')) {
      $parts = explode('/', $file);

      return array_pop($parts);
    }

    return $file;
  }

  /**
  * Get the current file path.
  */
  public function getPath() : string
  {
    return $this->path;
  }

  /**
  * Get the current files mime type.
  */
  public function getMimeType() : string
  {
    try {
      return MimeType::getMimeType($this->getPath());

    } catch (\RuntimeException $e) {
      throw new FileException(sprintf("We could not determine the files mime type for file [%s]", $this->getPath()));
    }
  }

}
