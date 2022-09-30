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

namespace Twipsi\Foundation\Application;

use Twipsi\Foundation\Exceptions\BindingException;
use Twipsi\Support\Bags\ArrayBag as Container;

class ImplantRegistry extends Container
{
  /**
  * Bind a parameter dependency to an abstract.
  */
  public function bind(string $abstract, array $parameters) : void
  {
    // If there are no parameters.
    if (! $parameters) {
      throw new BindingException(sprintf("No parameters provided to bind for abstract {%s}", $abstract));
    }

    $this->set($abstract, $parameters);
  }

}
