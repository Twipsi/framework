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
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;

class GlobalSessionDriver implements SessionDriverInterface
{
  /**
  * Validity time in minutes.
  */
  protected $validity;

  /**
  * Construct global driver.
  */
  public function __construct(int $minutes)
  {
    $this->validity = $minutes;

    // Start PHP session if it hasnt been yet
    if (! headers_sent()) {
      session_start();
    }
  }

  /**
  * Read session from driver.
  */
  public function read(string $id) :? string
  {
    if (! isset($_SESSION[$id])) {
      return null;
    }

    $current = $_SESSION[$id];

    if (! isset($current['stamp']) || !isset($current['content'])) {
      return null;
    }

    if ($this->validity < Chronos::date()->travel($current['stamp'])->minutesPassed()) {
      return null;
    }

    return $current['content'];
  }

  /**
  * Write to session driver.
  */
  public function write(string $id, string $content) : void
  {
    $_SESSION[$id] =
      [
        'content' => $content,
        'stamp'   => Chronos::date()->getDateTime(),
      ];
  }

  /**
  * Destroy session in driver.
  */
  public function destroy(string $id) : void
  {
    if (isset($_SESSION[$id])) {
      unset($_SESSION[$id]);
    }
  }

  /**
  * Validate all sessions in driver.
  */
  public function clean(int $lifetime) : void
  {
    foreach($_SESSION ?? [] as $id => $session) {

      if (! isset($session['stamp'])) {
        continue;
      }

      $expired = Chronos::date()->travel($session['stamp'])->minutesPassed();

      if($expired > $this->validity ) {
        unset( $_SESSION[ $id ] );
      }
    }
  }

}
