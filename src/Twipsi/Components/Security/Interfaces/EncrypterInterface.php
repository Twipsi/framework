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

namespace Twipsi\Components\Security\Interfaces;

interface EncrypterInterface
{
  /**
  * Generate a valid salt key for sodium.
  */
  public function generate(mixed $algorithm) : string;

  /**
  * Create MAC for the encryption.
  *
  * @throws UnknownAlgorithmException
  */
  public function hash(string $data, string $iv, string $method) : string;

  /**
  * Check if the decoded hash is valid.
  */
  public function validate(array $data) : bool;

  /**
  * Decrypt data using sodium lib.
  *
  * @throws DecrypterException
  */
  public function decrypt(string $data) : string|bool;

  /**
  * Encrypt data using sodium lib.
  *
  * @throws EncrypterException
  */
  public function encrypt(string $data) : string;
}
