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

namespace Twipsi\Components\Url;

use Twipsi\Components\Http\HttpRequest;
use Twipsi\Support\Chronos;
use Twipsi\Support\Hasher;
use Twipsi\Support\Str;

final class SignatureVerifier
{
    /**
     * Application key.
     *
     * @var string
     */
    protected string $appKey;

    /**
     * Construct SignatureVerifier.
     * 
     * @param  string $appKey
     */
    public function __construct(string $appKey)
    {
        $this->appKey = $appKey;
    }

    /**
     * Verify request signature.
     *
     * @param HttpRequest $request
     * @return bool
     */
    public function verifySignature(HttpRequest $request): bool
    {
        return $this->hasValidSignature($request) 
            && $this->signatureIsNotExpired($request);
    }

    /**
     * Check if the signature is still valid.
     * 
     * @param HttpRequest $request
     * @return bool
     */
    protected function hasValidSignature(HttpRequest $request): bool
    {
        $query = $request->headers->get('query-string');

        $url = $request->url->getAbsoluteUrl(false);

        $filtered = array_filter(explode('&', $query),
            function ($v) {
                return Str::hay($v)->before('=') !== 'signature';
            });

        $original = $url.'?'.implode('&', $filtered);
        $signature = Hasher::hashData($original, $this->appKey);

        return Hasher::checkHash($signature, $request->input('signature'));
    }

    /**
     * Check if the signature has not expired.
     * 
     * @param HttpRequest $request
     * @return bool
     */
    public function signatureIsNotExpired(HttpRequest $request): bool
    {
        return Chronos::date()->stamp() < (int)$request->input('expires');
    }
}
