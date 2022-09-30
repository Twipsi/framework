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

namespace Twipsi\Support\Traits;

use ReflectionClass;
use ReflectionProperty;

trait Serializable
{
  /**
   * Called when an object gets serialized.
   * 
   * @return array
   */
  public function __sleep() : array
  {
    $properties = (new ReflectionClass($this))
            ->getProperties(ReflectionProperty::IS_PUBLIC);

    foreach ($properties as $property) {
      $saved[] = $property->getName();
    }

    return $saved ?? [];
  }

  /**
  * Restore the object with additional logics.
  */
  public function __wakeup() : void {}

}
