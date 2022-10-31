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

namespace Twipsi\Components\Security\Csrf;

use Twipsi\Components\Http\Interfaces\StateProviderInterface;
use Twipsi\Components\Security\Csrf\Interfaces\TokenProviderInterface;
use Twipsi\Components\Session\SessionItem;
use Twipsi\Support\Hasher;
use Twipsi\Support\KeyGenerator;

class SessionTokenProvider implements TokenProviderInterface
{
    /**
     * Session Token.
     *
     * @var string
     */
    private string $token;

    /**
     * The csrf key name.
     *
     * @var string
     */
    private readonly string $csrfKey;

    /**
     * The csrf token length.
     *
     * @var int
     */
    private readonly int $csrfLength;

    /**
     * Token provider constructor.
     *
     * @param SessionItem|null $session
     * @param string $csrfKey
     * @param int $csrfLength
     */
    public function __construct(?SessionItem $session, string $csrfKey, int $csrfLength)
    {
        $this->csrfKey = $csrfKey;
        $this->csrfLength = $csrfLength;

        $this->setToken($session);
    }

    /**
     * Check if instance has token.
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return ! is_null($this->token);
    }

    /**
     * Generate and save a new instance token.
     *
     * @return TokenProviderInterface
     */
    public function refreshToken(): TokenProviderInterface
    {
        $this->token = $this->generateToken($this->csrfLength);
        var_dump('refreshing token');
        return $this;
    }

    /**
     * Generate a new token.
     *
     * @param int $length
     * @return string
     */
    public function generateToken(int $length = 32): string
    {
        return KeyGenerator::generateSecureKey($length);
    }

    /**
     * Check if the provided tokens match.
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        if (is_string($sessionToken = $this->getToken()) && !empty($token)) {
            return Hasher::checkHash($sessionToken, $token);
        }

        return false;
    }

    /**
     * Get the instance token.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Set token from session if available or generate a new one.
     *
     * @param StateProviderInterface|null $origin
     * @return TokenProviderInterface
     */
    public function setToken(null|StateProviderInterface $origin): TokenProviderInterface
    {
        if (!is_null($origin) && $origin->has($this->csrfKey)) {
            $this->token = $origin->csrf();
        }

        if (!$this->token) {
            $this->token = $this->generateToken($this->csrfLength);
        }

        return $this;
    }
}
