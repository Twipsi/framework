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

class Sodium implements EncrypterInterface
{
  /**
  * Construct encrypter.
  */
  public function __construct(protected string $appKey, protected string $algorithm, protected int $keyLength)
  {
    if (empty($appKey) || empty($algorithm)) {
      throw new EncrypterException('The provided application key or method is invalid');
    }
  }

  /**
  * Generate a valid salt key for sodium.
  */
  public function generate(mixed $algorithm = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) : string
  {
    return random_bytes($algorithm);
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

    if (strlen( $iv ) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
      return false;
    }

    return hash_equals($this->hash(base64_decode($data['data']), $iv), $data['mac']);
  }

  /**
  * Decrypt data using sodium lib.
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
    $encrypted = base64_decode($json['data']);
    $decrypted = sodium_crypto_secretbox_open($encrypted, $iv, $this->appKey);

    if (! $decrypted) {
      throw new DecrypterException('Could not decrypt the requested data');
    }

    sodium_memzero($encrypted);
    return $decrypted;
  }

  /**
  * Encrypt data using sodium lib.
  *
  * @throws EncrypterException
  */
  public function encrypt(string $data) : string
  {
    $iv = $this->generate();
    $encrypted = sodium_crypto_secretbox($data, $iv, $this->appKey);

    if (empty($encrypted)) {
      throw new EncrypterException('Failed to encrypt the requested data');
    }

    $json = Jso::hay(['iv' => base64_encode($iv),
                      'data' => base64_encode($encrypted),
                      'mac' => $this->hash($encrypted, $iv)
                      ])->encode( JSON_UNESCAPED_SLASHES );

    if (! Jso::valid()) {
      throw new EncrypterException('Failed to encode the requested data');
    }

    sodium_memzero($iv);
    return base64_encode($json);
  }

}
