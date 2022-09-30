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

use Twipsi\Components\Session\SessionItem;
use Twipsi\Support\Hasher;
use Twipsi\Support\KeyGenerator;
use Twipsi\Components\Security\Csrf\Interfaces\TokenProviderInterface;
use Twipsi\Components\Http\Interfaces\StateProviderInterface;

class SessionTokenProvider implements TokenProviderInterface
{
  /**
  * Session Token.
  */
  private string $token;

  /**
  * Token provider constructor
  */
  public function __construct(?SessionItem $session, private string $csrfKey, private int $csrfLength)
  {
    $this->setToken($session);
  }

  /**
  * Set token from session if available or generate a new one.
  */
  public function setToken(?StateProviderInterface $origin) : TokenProviderInterface
  {
    if (null !== $origin && $origin->has($this->csrfKey)) {
      $this->token = $origin->csrf();
    }

    if (! $this->token) {
      $this->token = $this->generateToken($this->csrfLength);
    }

    return $this;
  }

  /**
  * Get instance token.
  */
  public function getToken() : ?string
  {
    return $this->token;
  }

  /**
  * Check if instance has token.
  */
  public function hasToken() : bool
  {
    return null !== $this->token;
  }

  /**
  * Generate and save a new instance token.
  */
  public function refreshToken() : TokenProviderInterface
  {
    $this->token = $this->generateToken($this->csrfLength);

    return $this;
  }

  /**
  * Check if the provided tokens match.
  */
  public function validateToken(string $token) : bool
  {
    if (is_string($sessionToken = $this->getToken()) && is_string($token)) {
      return Hasher::checkHash($sessionToken, $token);
    }

    return false;
  }

  /**
  * Generate a token.
  */
  public function generateToken(int $length = 32) : string
  {
    return KeyGenerator::generateSecureKey($length);
  }

}
