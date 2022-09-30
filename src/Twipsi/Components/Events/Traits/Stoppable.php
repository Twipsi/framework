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

trait Stoppable
{
  /**
   * Propagation status.
   * 
   * @var bool
   */
  public bool $stopped = false;

  /**
   * Check if listener has stopped the propagation.
   * 
   * @return bool
   */
  public function isStopped() : bool
  {
    return $this->stopped;
  }

  /**
   * Stop the propagation.
   * 
   * @return void
   */
  public function stop() : void
  {
    $this->stopped = true;
  }
}
