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

use Twipsi\Components\Cookie\Cookie;
use Twipsi\Support\Hasher;
use Twipsi\Support\KeyGenerator;
use Twipsi\Components\Security\Csrf\Interfaces\TokenProviderInterface;
use Twipsi\Components\Http\Interfaces\StateProviderInterface;

class CookieTokenProvider implements TokenProviderInterface
{
  /**
  * Cookie Token.
  */
  private string $token;

  /**
  * Token provider constructor
  */
  public function __construct(?Cookie $cookie, private int $csrfLength)
  {
    $this->setToken($cookie);
  }

  /**
  * Set token from cookie if available or generate a new one.
  */
  public function setToken(?StateProviderInterface $origin) : TokenProviderInterface
  {
    if (null !== $origin && $origin->hasValue()) {
      $this->token = $origin->getValue();
    }

    if ( !$this->token) {
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
    if (is_string($cookieToken = $this->getToken()) && is_string($token)) {
      return Hasher::checkHash($cookieToken, $token);
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
