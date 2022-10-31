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

use Twipsi\Support\Chronos;
use Twipsi\Support\Bags\ArrayBag as Container;
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;

class ArraySessionDriver implements SessionDriverInterface
{
  /**
  * Sessions.
  */
  protected Container $sessions;

  /**
  * Validity time in minutes.
  */
  protected int $validity;

  /**
  * Construct session driver.
  */
  public function __construct(int $minutes)
  {
    $this->validity = $minutes;
    $this->sessions = new Container;
  }

  /**
  * Read session from driver.
  */
  public function read(string $id) :? string
  {
    if (! $this->sessions->has($id)) {
      return null;
    }

    $current = $_SESSION[$id];

    if (! isset($current['stamp']) || !isset($current['content'])) {
      return null;
    }

    if($this->validity < Chronos::date()->travel($current['stamp'])->minutesPassed()) {
      return null;
    }

    return $current['content'];
  }

  /**
  * Write to session driver.
  */
  public function write(string $id, string $content) : void
  {
    $this->sessions->set(
      $id, [
        'content' => $content,
        'stamp'   => Chronos::date()->getDateTime(),
      ]
    );
  }

  /**
  * Destroy session in driver.
  */
  public function destroy(string $id) : void
  {
    $this->sessions->delete($id);
  }

  /**
  * Clean all sessions in driver.
  */
  public function clean(int $lifetime) : void
  {
    $expired = Chronos::date()->subMinutes($lifetime)->getDateTime();

    foreach ($this->sessions as $id => $session) {

      if (! isset($session['stamp'])) {
        continue;
      }

      $expired = Chronos::date()->travel($session['stamp'])->minutesPassed();

      if ($expired > $this->validity) {
        $this->sessions->delete( $id );
      }
    }
  }

}
