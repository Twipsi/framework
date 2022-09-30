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

namespace Twipsi\Components\Security\Encrypters;

use Twipsi\Support\Str;
use Twipsi\Support\Jso;
use Twipsi\Components\Security\Interfaces\EncrypterInterface;
use Twipsi\Components\Security\Exceptions\EncrypterException;
use Twipsi\Components\Security\Exceptions\DecrypterException;
use Twipsi\Components\Security\Exceptions\UnknownAlgorithmException;

class OpenSSL implements EncrypterInterface
{
  /**
  * Construct openSSL encrypter.
  */
  public function __construct(protected string $appKey, protected string $algorithm)
  {
    if (empty($appKey) || empty($algorithm)) {
      throw new EncrypterException('The provided application key or method is invalid');
    }
  }

  /**
  * Generate a valid salt key for openssl.
  */
  public function generate(mixed $algorithm) : string
  {
    return random_bytes(openssl_cipher_iv_length($algorithm));
  }

  /**
  * Create MAC for the encryption.
  *
  * @throws UnknownAlgorithmException
  */
  public function hash(string $data, string $iv, string $method = 'sha256') : string
  {
    try {
      return hash_hmac($method, $iv.$data, $this->appKey);

    } catch (\ValueError $e) {
      throw new UnknownAlgorithmException(sprintf("The requested algorithm [%s] is not supported by (hash_hmac)", $method));
    }
  }

  /**
  * Check if the decoded hash is valid.
  */
  public function validate(array $data) : bool
  {
    // If we dont have all the required parts for decryption exit.
    if (! isset( $data['iv'], $data['data'], $data['mac'])) {
      return false;
    }

    // If the extracted iv is invalid exit.
    $iv = base64_decode($data['iv']);

    if (strlen( $iv ) !== openssl_cipher_iv_length($this->algorithm)) {
      return false;
    }

    return hash_equals($this->hash( $data['data'], $iv), $data['mac']);
  }

  /**
  * Decrypt data using Openssl algorithms.
  *
  * @throws DecrypterException
  */
  public function decrypt(string $data) : string|bool
  {
    $json = Jso::hay(base64_decode($data))->decode(true);

    if (! Jso::valid() || !$this->validate((array)$json)) {
      throw new DecrypterException('Could not validate the decrypted data pack');
    }

    $iv = base64_decode($json['iv']);
    $decrypted = openssl_decrypt($json['data'], $this->algorithm, $this->appKey, 0, $iv);

    if (! $decrypted ) {
      throw new DecrypterException('Could not decrypt the requested data');
    }

    return $decrypted;
  }

  /**
  * Encrypt data using Openssl algorithms.
  *
  * @throws EncrypterException
  */
  public function encrypt(string $data) : string
  {
    $iv = $this->generate($this->algorithm);
    $encrypted = openssl_encrypt($data, $this->algorithm, $this->appKey, 0, $iv);

    if (empty($encrypted)) {
      throw new EncrypterException('Failed to encrypt the requested data');
    }

    $json = Jso::hay(['iv' => base64_encode($iv),
                      'data' => $encrypted,
                      'mac' => $this->hash($encrypted, $iv)
                      ])->encode(JSON_UNESCAPED_SLASHES);

    if (! Jso::valid()) {
      throw new EncrypterException('Failed to encode the requested data');
    }

    return base64_encode($json);
  }

}
