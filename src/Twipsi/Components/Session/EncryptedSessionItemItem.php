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

namespace Twipsi\Components\Session;

use Twipsi\Components\Session\Interfaces\SessionDriverInterface;
use Twipsi\Components\Session\SessionItem;
use Twipsi\Components\Security\Encrypter;

class EncryptedSessionItemItem extends SessionItem
{
  /**
  * Encrypter object.
  */
  protected Encrypter $encrypter;

  /**
  * Encrypted session item Constructor
  */
  public function __construct(string $name, SessionDriverInterface $driver, Encrypter $encrypter, int $idLength, int $csrfLength)
  {
    $this->encrypter = $encrypter;

    parent::__construct($name, $driver, $idLength, $csrfLength);
  }

  /**
  * Decrypt data with encrypter.
  */
  protected function decrypt(string $data) : string
  {
    $data = $this->encrypter->decrypt($data, $this->id);

    if (!$data) {
      return serialize([]);
    }

    return $data;
  }

  /**
  * Encrypt data with encrypter.
  */
  protected function encrypt(string $data) : string
  {
    return $this->encrypter->encrypt($data, $this->id);
  }

}
