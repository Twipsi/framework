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

use Twipsi\Support\Str;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;

class DirectoryManager
{
  /**
   * List all directories in a directory.
   * 
   * @param string $path
   * 
   * @return array
   */
  public function list(string $path, array $except = []): array
  {
    foreach(glob($path.'/*') as $path) {

      if(is_dir($path) && !in_array($path, $except)) {

        // Clean the directory seperators.
        $dirs[] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
      }
    }

    return $dirs ?? [];
}

  /**
  * Create a directory.
  *
  * @throws DirectoryManagerException
  */
  public function make(string $path, int $chmod = 0755, bool $recursive = false) : void
  {
    if (! @mkdir($path, $chmod, $recursive)) {
      throw new DirectoryManagerException(sprintf("Could not create directory [%s]", $path));
    }
  }

  /**
  * Move a driectory to another location.
  *
  * @throws DirectoryManagerException
  */
  public function move(string $path, string $location) : void
  {
    if (! is_dir($path)) {
      throw new DirectoryManagerException(sprintf("Directory To be moved [%s] does not exist", $path));
    }

    if (! @rename($path, $location)) {
      throw new DirectoryManagerException(sprintf("Could not move directory [%s] to location [%s]", $path, $location));
    }
  }

  /**
  * Copy a directory and all its contents.
  *
  * @throws DirectoryManagerException
  */
  public function copy(string $path, string $location) : void
  {
    if (! is_dir($path)) {
      throw new DirectoryManagerException(sprintf("Directory To be copied [%s] does not exist", $path));
    }

    // Create the directory recursively if it doesnt exist.
    if (! is_dir($location)) {
      $this->make($location, 0777, true);
    }

    // Parse through the directory and attempt to copy everything.
    foreach (glob($path.'*', GLOB_BRACE) as $file) {

      // If the current item is a directory we will copy the directory
      // with the same name and copy all the file to it.
      if (is_dir($file)) {

        $name = Str::hay($file)->afterLast('/').'/';

        try {
          $this->copy($file.'/', $location.$name);
          continue;

        } catch (DirectoryManagerException $e) {
          throw new DirectoryManagerException(sprintf("Directory [%s] in directory [%s] could not be copied", $file, $path));
        }
      }

      // If the item is a file, create a file item and copy it.
      try {
        $file = new FileItem($file);
        $file->copy($location, $file->getBaseName());

      } catch (FileException $e) {
        throw new DirectoryManagerException(sprintf("File [%s] in directory [%s] could not be copied", $file->getBaseName(), $path));
      }
    }
  }

  /**
  * Delete a directory entirely.
  *
  * @throws DirectoryManagerException
  */
  public function delete(string $path, bool $strict = true) : void
  {
    if (! is_dir($path)) {
      throw new DirectoryManagerException(sprintf("Directory To be deleted [%s] does not exist", $path));
    }

    // Parse through the directory and attempt to copy everything.
    foreach (glob($path.'*', GLOB_BRACE) as $file) {

      // If the current item is a directory we will delete the directory
      // with the same name and delete all the files in it.
      if (is_dir($file) && !is_link($file)) {

        try {
          $this->delete($file.'/', $strict);
          continue;

        } catch (DirectoryManagerException $e) {
          throw new DirectoryManagerException(sprintf("Directory [%s] in directory [%s] could not be deleted", $file, $path));
        }
      }

      // If the item is a file, create a file item and delete it.
      try {
        $file = new FileItem($file);
        $file->delete();

      } catch (FileException $e) {
        throw new DirectoryManagerException(sprintf("File [%s] in directory [%s] could not be deleted", $file->getBaseName(), $path));
      }
    }

    if ($strict && ! @rmdir($path) ) {
      throw new DirectoryManagerException(sprintf("Directory [%s] could not be deleted", $path));
    }
  }

  /**
  * Empty a specific directory keeping the directory.
  *
  * @throws DirectoryManagerException
  */
  public function empty(string $path) : void
  {
    $this->delete($path, false);
  }

}
