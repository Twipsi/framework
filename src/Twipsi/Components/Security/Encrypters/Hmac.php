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
use Twipsi\Support\KeyGenerator;
use Twipsi\Components\Security\Interfaces\EncrypterInterface;
use Twipsi\Components\Security\Exceptions\EncrypterException;
use Twipsi\Components\Security\Exceptions\DecrypterException;
use Twipsi\Components\Security\Exceptions\UnknownAlgorithmException;

class Hmac implements EncrypterInterface
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
  * Generate a valid salt key for hmac.
  */
  public function generate(mixed $algorithm) : string
  {
    $length = $algorithm === 'sha256' ? 32 : 64;
    return KeyGenerator::generateByteKey($length);
  }

  /**
  * Create MAC for the encryption.
  *
  * @throws UnknownAlgorithmException
  */
  public function hash(string $data, string $iv = null, string $method = 'sha256') : string
  {
    if (null === $iv) {
      $iv = $this->generate($this->algorithm);
    }

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
    if (! isset($data['hmac'])) {
      return false;
    }

    $baseLength = strlen(base64_encode($this->generate($this->algorithm)));
    $iv = base64_decode(Str::hay($data['hmac'])->pull($this->keyLength*2, $baseLength));

    if (strlen($iv) !== $this->keyLength) {
      return false;
    }

    return true;
  }

  /**
  * Decrypt data using Openssl algorithms.
  *
  * @throws DecrypterException
  */
  public function decrypt(string $data) : string|bool
  {
    $json = Jso::hay(base64_decode($data))->decode(true);

    if (! Jso::valid() || ! $this->validate((array)$json)) {
      throw new DecrypterException('Could not validate the decrypted data pack');
    }

    $hmac = Str::hay($json['hmac'])->pull(0, $this->keyLength*2);

    $baseLength = strlen(base64_encode($this->generate($this->algorithm)));
    $iv = base64_decode(Str::hay($json['hmac'])->pull($this->keyLength*2, $baseLength));

    $decrypted = base64_decode(Str::hay($json['hmac'])->pull(($this->keyLength*2) + $baseLength, null));

    if (empty($decrypted)) {
      throw new DecrypterException('Could not decrypt the requested data');
    }

    if (! hash_equals($hmac, $this->hash($decrypted, $iv.$this->appKey))) {
      return false;
    }

    return $decrypted;
  }

  /**
  * Encrypt data using hmac
  *
  * @throws EncrypterException
  */
  public function encrypt(string $data) : string
  {
    $iv = $this->generate($this->algorithm);
    $encrypted = $this->hash($data, $iv);

    if (empty($encrypted)) {
      throw new EncrypterException('Failed to encrypt the requested data');
    }

    $json = Jso::hay(['hmac' => $this->hash($data, $iv.$this->appKey) . base64_encode($iv) . base64_encode($data)])
                    ->encode(JSON_UNESCAPED_SLASHES);

    if (! Jso::valid()) {
      throw new EncrypterException('Failed to encode the requested data');
    }

    return base64_encode($json);
  }

}
