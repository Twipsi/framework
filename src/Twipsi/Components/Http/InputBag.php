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

namespace Twipsi\Components\Http;

use Twipsi\Support\Str;
use Twipsi\Support\Jso;
use Twipsi\Support\Bags\RecursiveArrayBag;

class InputBag extends RecursiveArrayBag
{
  /**
  * Container of original data.
  */
  private RecursiveArrayBag $original;

  /**
  * Input bag constructor.
  */
  public function __construct(HttpRequest $request, array $data = [])
  {
    $this->original = new RecursiveArrayBag( $data );

    if ($request->isRequestMethodData()) {

      // Check if we have json data
      if (Str::hay(trim($contents = file_get_contents('php://input')))->first() === '{') {

        $json = Jso::hay($contents)->decode(true);
        if (Jso::valid() && is_array($json)) {
          $data = $json;
        }
      }
    }

    parent::__construct( $data );
  }

  /**
  * Returns the original input data.
  */
  public function original() : RecursiveArrayBag
  {
    return $this->original;
  }

}
