<?php

namespace Twipsi\Tests\Foundation\Fakes\Middlewares;

use Closure;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Components\Http\HttpRequest as Request;

class HookMiddleware implements MiddlewareInterface
{
    public function resolve(Request $request, ...$args) : Closure
    {
        return fn($v) => $v;
    }
}