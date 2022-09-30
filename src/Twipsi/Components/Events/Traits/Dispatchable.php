<?php
/*
* This file is part of the Twipsi package.
*
* (c) Petrik Gábor <twipsi@twipsi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Twipsi\Components\Events\Traits;

use Twipsi\Facades\Event;

trait Dispatchable
{
  /**
   * If the event is dispatchable.
   * 
   * @var bool
   */
  protected static bool $dispatchable = true;

  /**
   * Dispatch the event staticly.
   * 
   * @param mixed ...$args
   * 
   * @return void
   */
  public static function dispatch(...$args) : void
  {
    if (self::$dispatchable) {

      Event::dispatch(new self(...$args));
    }
  }

  /**
   * Conditional hook before dispatching.
   * 
   * @param array $conditions
   * @param mixed ...$args
   * 
   * @return void
   */
  public static function dispatchIf(array $conditions, ...$args) : void
  {
    self::$dispatchable = ! in_array(false, $conditions);

    static::dispatch(...$args);
  }

  /**
   * Conditional hook before dispatching.
   * 
   * @param array $conditions
   * @param mixed ...$args
   * 
   * @return static
   */
  public function dispatchElse(array $conditions, ...$args) : void
  {
    self::$dispatchable = ! in_array(true, $conditions);

    static::dispatch(...$args);
  }

}
