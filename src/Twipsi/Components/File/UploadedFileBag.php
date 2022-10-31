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

use Twipsi\Support\Bags\ArrayBag as Container;
use InvalidArgumentException;

class UploadedFileBag extends Container
{
  /**
  * Container of original data.
  */
  private Container $original;

  /**
  * File bag constructor.
  */
  public function __construct(array $files = [])
  {
    $this->original = new Container($files);

    if (! empty($files)) {

      foreach ($files as $group => $file) {
        $this->set($group, $file);
      }
    }
  }

  /**
  * Set an http file into the container.
  */
  public function set(string $key, mixed $value, bool $recursive = true) : static
  {
    if (! is_array($value) && ! $value instanceof UploadedFile) {
      throw new InvalidArgumentException('Unexpected file data recieved, data should be an array or object.');
    }

    return parent::set($key, $this->compile($value), $recursive);
  }

  /**
  * Compile file data as an HttpFile object.
  */
  public function compile(array|UploadedFile $data) : null|array|UploadedFile
  {
    if ($data instanceof UploadedFile) {
      return $data;
    }

    if($this->single($files = $this->recompile($data))) {
      return !$this->error($files) ? new UploadedFile($files) : null;
    }

    foreach ($files as &$parts) {
      $parts = !$this->error($parts) ? new UploadedFile($parts) : null;
    }

    return $files;
  }

  /**
  * Check if we have an error uploading file.
  */
  protected function error(array $parts) : bool
  {
    return UPLOAD_ERR_NO_FILE === $parts['error'];
  }

  /**
  * Restructure file array to be the same as not diverse.
  */
  protected function recompile(array $parts) : array
  {
    if ($this->single($parts)) {
      return $parts;
    }

    foreach ($parts['name'] as $name => $value) {

      $data = [
        'name' => $parts['name'][$name],
        'type' => $parts['type'][$name],
        'tmp_name' => $parts['tmp_name'][$name],
        'error' => $parts['error'][$name],
        'size' => $parts['size'][$name],
      ];

      $compiled[$name] = $data;
    }

    return $compiled ?? [];
  }

  /**
  * Check if data is single image data.
  */
  public function single(array $parts) : bool
  {
    if ( !isset($parts['name']) || is_array($parts['name'])) {
      return false;
    }

    $required = ['error', 'name', 'size', 'tmp_name', 'type'];
    $keys = array_keys($parts);
    sort($keys);

    if (! empty(array_diff($required, $keys))) {
      return false;
    }

    return true;
  }

  /**
  * Returns the original input data.
  */
  public function original() : Container
  {
    return $this->original;
  }

}
