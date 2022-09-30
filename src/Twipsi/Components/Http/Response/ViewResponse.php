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

use Twipsi\Components\View\ViewFactory;
use Twipsi\Components\View\View;
use Twipsi\Components\Http\Response\Response;

use Twipsi\Facades\App;

class ViewResponse extends Response
{
  /**
  * View response constructor.
  */
  public function __construct(string $view, array $data, int $code = 200, array $headers = [])
  {
    $view = $this->buildView($view, $data);

    parent::__construct($view->render(), $code, $headers);
  }

  /**
  * Render the requested view with the provided data.
  */
  protected function buildView(string $view = null, array $data = []) : ViewFactory|View
  {
    if(is_null($view)) {
      return App::get('view.factory');
    }

    return App::get('view.factory')->create($view, $data);
  }

}
