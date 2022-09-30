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

use Twipsi\Components\Cookie\CookieBag;
use Twipsi\Components\Http\Exceptions\NotSupportedException;
use Twipsi\Components\Security\Encrypter;
use Twipsi\Components\Session\Drivers\ArraySessionDriver;
use Twipsi\Components\Session\Drivers\CookieSessionDriver;
use Twipsi\Components\Session\Drivers\DatabaseSessionDriver;
use Twipsi\Components\Session\Drivers\FileSessionDriver;
use Twipsi\Components\Session\Drivers\GlobalSessionDriver;
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Support\Applicables\Cookies;
use Twipsi\Support\Applicables\Database;
use Twipsi\Support\Bags\ArrayAccessibleBag as Container;

class SessionSubscriber
{
  use Database, Cookies;

  /**
  * Session drivers.
  */
  public Container $drivers;

  /**
  * Construct session subscriber.
  */
  public function __construct(protected ConfigRegistry $config, protected Encrypter $encrypter)
  {
    $this->drivers = new Container;
  }

  /**
  * Create new driver and session.
  */
  public function create(string $driver) : SessionItem
  {
    switch ($driver) {
      case 'array' :
        return $this->createArrayDrivenSession();

      case 'database' :
          $this->config->get('database')->set('driver', $this->config->get('session.connection'));

        return $this->createDatabaseDrivenSession(
          $this->db->create($this->config->get('database.driver'))
        );

      case 'file' :
        return $this->createFileDrivenSession();

      case 'cookie' :
        return $this->createCookieDrivenSession($this->cookies);

      case 'global' :
        return $this->createGlobalDrivenSession();
    }

    throw new NotSupportedException(sprintf('The requested driver "%s" is not supoorted', $driver));
  }

  /**
  * Register created driver and session.
  */
  private function register(string $name, SessionDriverInterface $driver) : SessionItem
  {
    $this->drivers->set($name, $session = $this->createSession($driver));

    return $session;
  }

  /**
  * Create new session with array driver.
  */
  public function createArrayDrivenSession() : SessionItem
  {
    return $this->register('array', $this->createArraySessionDriver());
  }

  /**
  * Create new session with database driver.
  */
  public function createDatabaseDrivenSession($connection) : SessionItem
  {
    return $this->register('database', $this->createDatabaseSessionDriver($connection));
  }

  /**
  * Create new session with file driver.
  */
  public function createFileDrivenSession() : SessionItem
  {
    return $this->register('file', $this->createFileSessionDriver());
  }

  /**
  * Create new session with cookie driver.
  */
  public function createCookieDrivenSession(CookieBag $cookies) : SessionItem
  {
    return $this->register('cookie', $this->createCookieSessionDriver($cookies));
  }

  /**
  * Create new session with global driver.
  */
  public function createGlobalDrivenSession() : SessionItem
  {
    return $this->register('global', $this->createGlobalSessionDriver());
  }

  /**
  * Create new array session driver.
  */
  protected function createArraySessionDriver() : SessionDriverInterface
  {
    return new ArraySessionDriver(
      $this->config->get('session.lifetime')
    );
  }

  /**
  * Create new file session driver.
  */
  protected function createFileSessionDriver(): SessionDriverInterface
  {
    return new FileSessionDriver(
      $this->config->get('session.files'),
      $this->config->get('session.lifetime')
    );
  }

  /**
  * Create new database session driver.
  */
  protected function createDatabaseSessionDriver($connection): SessionDriverInterface
  {
    return new DatabaseSessionDriver(
      $connection,
      $this->config->get('session.table'),
      $this->config->get('session.lifetime')
    );
  }

  /**
  * Create new cookie session driver.
  */
  protected function createCookieSessionDriver(CookieBag $cookies): SessionDriverInterface
  {
    return new CookieSessionDriver(
      $cookies,
      $this->config->get('session.lifetime')
    );
  }

  /**
  * Create new global session driver.
  */
  protected function createGlobalSessionDriver(): SessionDriverInterface
  {
    return new GlobalSessionDriver(
      $this->config->get('session.lifetime')
    );
  }

  /**
  * Create a (encrypted) session item.
  */
  private function createSession(SessionDriverInterface $driver) : SessionItem
  {
    if ($this->config->get('session.encrypt')) {
      return new EncryptedSessionItemItem($this->config->get('session.name'), $driver, $this->encrypter,
        $this->config->get('session.id_length'), $this->config->get('security.csrf_length')
      );
    }

    return new SessionItem($this->config->get('session.name'), $driver,
      $this->config->get('session.id_length'), $this->config->get('security.csrf_length')
    );
  }

}
