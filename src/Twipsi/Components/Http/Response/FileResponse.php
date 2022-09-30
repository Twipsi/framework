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

namespace Twipsi\Components\Http\Response;

use Twipsi\Components\Http\Response\Response;

class FileResponse extends Response
{
  /**
  * JSON Response constructor
  */
  public function __construct(mixed $content, int $code = 200, array $headers = [])
  {
    parent::__construct($content, $code, $headers);
  }
}
