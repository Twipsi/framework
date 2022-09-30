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

namespace Twipsi\Bridge\Middlewares;

use Closure;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Router\Exceptions\InvalidSignatureException;
use Twipsi\Components\Url\SignatureVerifier;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class ValidateSignature implements MiddlewareInterface
{
    /**
     * Configuration collection.
     *
     * @var ConfigRegistry
     */
    protected ConfigRegistry $config;

    /**
     * Construct middleware
     *
     * @param ConfigRegistry $config
     */
    public function __construct(ConfigRegistry $config)
    {
        $this->config = $config;
    }

    /**
     * Resolve middleware logics.
     *
     * @param Request $request
     * @param ...$args
     * @return Closure|bool
     * @throws InvalidSignatureException
     */
    public function resolve(Request $request, ...$args): Closure|bool
    {
        if (! (new SignatureVerifier($this->config->get('security.app_key')))
                ->verifySignature($request)) {

            throw new InvalidSignatureException();
        }

        return true;
    }
}
