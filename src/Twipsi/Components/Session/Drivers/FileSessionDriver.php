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

namespace Twipsi\Components\Session\Drivers;

use Twipsi\Components\File\FileBag as Container;
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;
use Twipsi\Support\Chronos;

class FileSessionDriver implements SessionDriverInterface
{
  /**
  * Validity time in minutes.
  */
  protected int $validity;

  /**
  * Session files.
  */
  protected Container $files;

  /**
  * Construct driver.
  */
  public function __construct(string $location, int $minutes)
  {
    $this->validity = $minutes;
    $this->files = new Container($location);
  }

  /**
  * Read session from driver.
  */
  public function read(string $id) :? string
  {
    if (! $this->files->has($id)) {
      return null;
    }

    if ($this->files->modified($id) < Chronos::date()->subMinutes($this->validity)->stamp()) {
      return null;
    }

    return $this->files->get($id);
  }

  /**
  * Write to session driver.
  */
  public function write(string $id, string $content) : void
  {
    $this->files->put($id, $content);
  }

  /**
  * Destroy session in driver.
  */
  public function destroy(string $id) : void
  {
    $this->files->delete($id);
  }

  /**
  * Validate all sessions in driver.
  */
  public function clean(int $lifetime) : void
  {
    foreach($this->files as $file) {

      if($this->files->modified($file) >= Chronos::date()->subMinutes($this->validity)->stamp()) {
        continue;
      }

      $this->files->delete($file);
    }
  }

}
