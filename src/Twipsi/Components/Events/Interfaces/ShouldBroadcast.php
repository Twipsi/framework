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

use Twipsi\Components\Broadcasting\Channels\ChannelInterface as Channel;

interface ShouldBroadcast
{
  /**
   * Set the broadcasting channel.
   * 
   * @return Channel
   */
  public function broadcastVia() : Channel;

  /**
   * Set a condition before broadcasting.
   * 
   * @return bool
   */
  public function broadcastIf() : bool;

  /**
   * Set a broadcasting name.
   * 
   * @return bool
   */
  public function broadcastAs() : bool;

  /**
   * Broadcast with specified data.
   * 
   * @return array
   */
  public function broadcastWith() :array;
}
