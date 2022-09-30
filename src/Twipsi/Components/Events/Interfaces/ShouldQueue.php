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

namespace Twipsi\Components\Events\Interfaces;

interface ShouldQueue
{
  /**
   * Set the queue channel.
   * 
   * @return string
   */
  public function queueVia() : string;

    /**
     * Set a condition before queueing.
     *
     * @param EventInterface $event
     * @return bool
     */
  public function queueIf(EventInterface $event) : bool;
}
