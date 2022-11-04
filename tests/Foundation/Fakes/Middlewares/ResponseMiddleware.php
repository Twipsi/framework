<?php

namespace Twipsi\Tests\Foundation\Fakes\Middlewares;

use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Http\Response\RedirectResponse;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Components\Http\HttpRequest as Request;

class ResponseMiddleware implements MiddlewareInterface
{
    public function resolve(Request $request, ...$args) : ResponseInterface
    {
        return new RedirectResponse('/');
    }
}