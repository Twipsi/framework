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

namespace Twipsi\Tests;

use PHPUnit\Framework\TestCase;
use Twipsi\Components\Security\Encrypter;
use InvalidArgumentException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Components\Security\Interfaces\EncrypterInterface;
use Twipsi\Components\Security\Encrypters\Sodium;
use Twipsi\Components\Security\Encrypters\Hmac;
use Twipsi\Components\Security\Encrypters\OpenSSL;

final class EncrypterTest extends TestCase
{
  public function testShouldBuildAnEncrypterInterface(): void
  {
    $class = new Encrypter('Y#;AmEt#|z}3rn2u;|ivWm^`a1ms@id4', 'aes-256-cbc');

    $this->assertInstanceOf( EncrypterInterface::class, $class->getEncrypter());
  }

  public function testShouldEncryptAndDecrypt(): void
  {
    $class = new Encrypter('Y#;AmEt#|z}3rn2u;|ivWm^`a1ms@id4', 'aes-256-cbc');

    $data = 'Encrypt me please.';
    $encryption = $class->encrypt( $data );

    $this->assertSame( $data, $class->decrypt( $encryption ));
  }

  public function testCannotBeCreatedFromInvalidAlgorithm(): void
  {
    $this->expectException(NotSupportedException::class);

    new Encrypter('Y#;AmEt#|z}3rn2u;|ivWm^`a1ms@id4', 'sha2545456');
  }

  public function testCannotBeCreatedFromInvalidKey(): void
  {
    $this->expectException(InvalidArgumentException::class);

    new Encrypter('Y#;AmEt#', 'sha256');
  }

}
