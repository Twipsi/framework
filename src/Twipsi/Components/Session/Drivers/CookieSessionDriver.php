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
use Twipsi\Support\Jso;
use Twipsi\Components\Cookie\Cookie;
use Twipsi\Components\Cookie\CookieBag;
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;

class CookieSessionDriver implements SessionDriverInterface
{
  /**
  * Validity time in minutes.
  */
  protected int $validity;

  /**
  * Cookies.
  */
  protected CookieBag $cookies;

  /**
  * Construct cookie driver.
  */
  public function __construct(CookieBag $cookies, int $minutes)
  {
    $this->validity = $minutes;
    $this->cookies = $cookies;
  }

  /**
  * Read session from cookie.
  */
  public function read(string $id) :? string
  {
    if (! $this->cookies->has($id)) {
      return null;
    }

    if (! $current = Jso::hay($this->cookies->get($id)->getValue())->decode(true)) {
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
    $this->cookies->queue($id,
      Jso::hay([
        'content' => $content,
        'stamp'   => Chronos::date()->getDateTime(),
      ])->encode(),
      Chronos::date()->addMinutes($this->validity)->stamp()
    );
  }

  /**
  * Destroy session in driver.
  */
  public function destroy(string $id) : void
  {
    $this->cookies->expire($id);
  }

  /**
  * Validate all sessions in driver.
  */
  public function clean(int $lifetime) : void
  {
    return;
  }

}
