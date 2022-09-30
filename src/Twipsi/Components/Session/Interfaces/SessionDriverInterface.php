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

namespace Twipsi\Components\Session\Interfaces;

interface SessionDriverInterface
{
  /**
  * Read session from driver
  *
  * @param string $id
  * @return string|null
  */
  public function read( string $id ) :? string;

  /**
  * Write to session driver
  *
  * @param string $id
  * @param string $content
  * @return mixed
  */
  public function write( string $id, string $content ) : void;

  /**
  * Destroy session in driver
  *
  * @param string $id
  * @return void
  */
  public function destroy( string $id ) : void;

  /**
  * Validate a session in driver
  *
  * @param int $lifetime
  * @return void
  */
  public function clean( int $lifetime ) : void;
}
