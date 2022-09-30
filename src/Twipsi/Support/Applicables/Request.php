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

use Twipsi\Components\Http\HttpRequest;

trait Request
{
  protected HttpRequest $request;

  /**
  * Set the request instance
  */
  public function appendRequest(HttpRequest $request) : static
  {
    $this->request = $request;

    return $this;
  }

  /**
  * Get the request instance
  */
  public function getRequest()
  {
    return $this->request;
  }

}
