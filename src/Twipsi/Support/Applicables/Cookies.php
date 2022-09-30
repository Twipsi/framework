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

namespace Twipsi\Support\Applicables;

use Twipsi\Components\Cookie\CookieBag;

trait Cookies
{
  protected CookieBag $cookies;

  /**
  * Set the cookies instance.
  */
  public function appendCookies(CookieBag $cookies) : static
  {
    $this->cookies = $cookies;

    return $this;
  }

  /**
  * Get the cookies instance.
  */
  public function getCookies()
  {
    return $this->cookies;
  }

}
