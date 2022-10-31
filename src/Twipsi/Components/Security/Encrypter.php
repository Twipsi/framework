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

namespace Twipsi\Components\Security;

use InvalidArgumentException;
use Twipsi\Foundation\Exceptions\NotSupportedException;

use Twipsi\Components\Security\Encrypters\Sodium;
use Twipsi\Components\Security\Encrypters\Hmac;
use Twipsi\Components\Security\Encrypters\OpenSSL;
use Twipsi\Components\Security\Interfaces\EncrypterInterface;

use Twipsi\Support\Str;
use Twipsi\Support\Arr;

class Encrypter
{
  /**
  * Supported algorithms.
  */
  protected EncrypterInterface $encrypter;

  /**
  * Supported algorithms.
  */
  protected array $supportedAlgorithms = [
    'sodium256' => [ 'size' => 32 ],
    'sha256' => [ 'size' => 32 ],
    'sha512' => [ 'size' => 64 ],
    'aes-128-cbc' => ['size' => 16 ],
    'aes-256-cbc' => ['size' => 32 ],
  ];

  /**
  * Construct encrypter with application key.
  */
  public function __construct(protected string $key, protected string $algorithm)
  {
    if (! $this->supported($this->algorithm)) {
      throw new NotSupportedException(sprintf('Encryption method %s is not supported', $this->algorithm));
    }

    if (! $this->validKey($this->key, $this->algorithm)) {
      throw new InvalidArgumentException(sprintf('Encryption key is not valid for method %s', $this->algorithm));
    }

    $this->encrypter = $this->loadEncrypter($this->algorithm);
  }

  /**
  * Check if encryption algorithm is supported.
  */
  public function supported(string $algorithm) : bool
  {
    $algorithm = strtolower($algorithm);

    return Arr::has($this->supportedAlgorithms, $algorithm);
  }

  /**
  * Check if provided key is valid.
  */
  public function validKey(string $key, string $algorithm) : bool
  {
    if (! isset($this->supportedAlgorithms[$algorithm])) {
      return false;
    }

    return $this->supportedAlgorithms[$algorithm]['size'] === strlen($key);
  }

  /**
  * Encrypt data using configured encrypter.
  */
  public function encrypt(string $data) : string
  {
    return $this->encrypter->encrypt($data);
  }

  /**
  * Decrypt data using configured encrypter.
  */
  public function decrypt(string $data) : string|bool
  {
    return $this->encrypter->decrypt($data);
  }

  /**
  * Load encrypter based on the provided algorithm.
  *
  * @throws NotSupportedException
  */
  protected function loadEncrypter(string $algorithm) : EncrypterInterface
  {
    // If we are using Sodium library to encrypt.
    if (Str::hay($algorithm)->resembles('sodium')) {

      if (! extension_loaded('sodium')) {
        throw new NotSupportedException('Sodium Library is not installed');
      }

      return new Sodium($this->key, $this->algorithm, $this->supportedAlgorithms[$algorithm]['size']);
    }

    // If we are using Hmac library to encrypt.
    if (Str::hay($algorithm)->resembles('sha') || Str::hay($algorithm)->resembles('ripemd')) {
      return new Hmac($this->key, $this->algorithm, $this->supportedAlgorithms[$algorithm]['size']);
    }

    // If we are using Openssl library to encrypt.
    if (Str::hay($algorithm)->resembles('aes')) {

      if (! extension_loaded('openssl')) {
        throw new NotSupportedException('Openssl Library is not installed');
      }

      return new openSSL($this->key, $this->algorithm);
    }

    throw new NotSupportedException('Requested encryption library is not supported by Twipsi');
  }

  /**
  * Return the active encrypter
  */
  public function getEncrypter() :? EncrypterInterface
  {
    return $this->encrypter;
  }

}
