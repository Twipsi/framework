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

namespace Twipsi\Components\View\Middlewares;

use Closure;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\View\ViewErrorBag;
use Twipsi\Components\View\ViewFactory;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class AppendErrorMessagesToView implements MiddlewareInterface
{
    /**
     * Middleware Constructor
     */
    public function __construct(protected ViewFactory $view)
    {
    }

    /**
     * Resolve middleware logics.
     */
    public function resolve(Request $request, ...$args): Closure|bool
    {
        $this->view->queue(
            "errors",
            $request->session()->getErrors() ?? new ViewErrorBag
        );

        return true;
    }
}
