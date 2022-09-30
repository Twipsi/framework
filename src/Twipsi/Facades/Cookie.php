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

namespace Twipsi\Facades;

use Twipsi\Facades\Facade;
use Twipsi\Facades\Interfaces\FacadeInterface;

class Cookie extends Facade implements FacadeInterface
{
  /**
  * Get the accessor name.
  */
  public static function getFacadeAccessorName() : string
  {
    return 'cookie';
  }

  /**
  * Get the class with namespace to load.
  */
  public static function getFacadeClassName() : string
  {
    return 'Twipsi\Components\Http\StateProvider\Cookie\CookieBag';
  }

}
