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

namespace Twipsi\Components\Security\Csrf\Interfaces;

use Twipsi\Components\Http\Interfaces\StateProviderInterface;

interface TokenProviderInterface
{
  /**
  * Set token from cookie if available or generate a new one.
  */
  public function setToken(?StateProviderInterface $origin) : TokenProviderInterface;

  /**
  * Get instance token.
  */
  public function getToken() : ?string;

  /**
  * Check if instance has token.
  */
  public function hasToken() : bool;

  /**
  * Generate and save a new instance token.
  */
  public function refreshToken() : TokenProviderInterface;

  /**
  * Check if the provided tokens match.
  */
  public function validateToken( string $token ) : bool;

  /**
  * Generate a token.
  */
  public function generateToken() : string;
}
