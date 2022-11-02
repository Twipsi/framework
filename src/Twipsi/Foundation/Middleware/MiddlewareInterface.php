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

namespace Twipsi\Foundation\Middleware;

use Twipsi\Components\Http\HttpRequest as Request;

interface MiddlewareInterface
{
    /**
     * Resolve middleware logics.
     */
    public function resolve(Request $request, ...$args);
}
