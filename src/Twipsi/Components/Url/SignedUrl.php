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

use InvalidArgumentException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Support\Chronos;
use Twipsi\Support\Hasher;

final class SignedUrl
{
    /**
     * THe Url generator.
     *
     * @var UrlGenerator
     */
    protected UrlGenerator $generator;

    /**
     * Signed Url generator constructor.
     *
     * @param UrlGenerator $generator
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Build and sign the route url.
     *
     * @param string $name
     * @param array $parameters
     * @param int|string|null $expires
     * @return string
     */
    public function sign(string $name, array $parameters = [], int|string $expires = null): string
    {
        $this->checkIfParametersAreAvailable($parameters);

        if (!is_null($expires)) {
            $parameters['expires'] = Chronos::date($expires)->stamp();
        }

        $parameters['signature'] = $this->createSignature($this->generator->route($name, $parameters));

        return $this->generator->route($name, $parameters);
    }

    /**
     * Check if the required parameter keys are free.
     *
     * @param array $parameters
     * @return bool
     */
    protected function checkIfParametersAreAvailable(array $parameters): bool
    {
        if (array_key_exists('signature', $parameters) || array_key_exists('expires', $parameters)) {
            throw new InvalidArgumentException("Reserved signed route parameters used as custom parameters.");
        }

        return true;
    }

    /**
     * Create a signature token base on the url.
     *
     * @param string $url
     * @return string
     */
    protected function createSignature(string $url): string
    {
        return Hasher::hashData($url, $this->generator->getSystemKey());
    }
}