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

use Twipsi\Support\Str;
use Twipsi\Support\Arr;
use Twipsi\Support\KeyGenerator;
use Twipsi\Support\Bags\RecursiveArrayBag as Container;
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;
use Twipsi\Components\Http\Interfaces\StateProviderInterface;

class SessionItem extends Container implements StateProviderInterface
{
  /**
  * Session ID.
  */
  protected string $id;

  /**
  * Session Name.
  */
  protected string $name;

  /**
  * Session started status.
  */
  protected bool $status;

  /**
  * Csrf session key.
  */
  public const CSRF_SESSION_KEY = '_csrf';

  /**
  * Session driver.
  */
  protected SessionDriverInterface $driver;

  /**
  * Session item Constructor.
  */
  public function __construct(string $name, SessionDriverInterface $driver, protected int $idLength, protected int $csrfLength)
  {
    $this->reset($driver, $name);
  }

  /**
  * Reset the session to default state.
  */
  public function reset(SessionDriverInterface $driver, string $name = null) : SessionItem
  {
    $this->setId();
    $this->flush();

    $this->name = $name ?? $this->name;
    $this->status = false;
    $this->driver = $driver;

    return $this;
  }

  /**
  * Load session data from driver into item object.
  */
  public function load() : void
  {
    if (! empty($data = $this->driver->read($this->id))) {

      if (method_exists($this, 'decrypt')) {
        $data = $this->decrypt($data);
      }

      $data = unserialize($data);
    }

    if (null !== $data && false !== $data && is_array($data)) {
      $this->merge($data);
    }
  }

  /**
  * Save current session to driver.
  */
  public function save() : void
  {
    if (method_exists($this, 'encrypt')) {
      $data = $this->encrypt($this->content());
    }

    $this->driver->write($this->id, $data ?? $this->content());

    $this->status = false;
  }

  /**
  * Start current session from driver
  */
  public function start() : SessionItem
  {
    $this->load();

    if ( !$this->has(self::CSRF_SESSION_KEY)) {
      $this->generateCsrf();
    }

    $this->prepareFlash();
    $this->status = true;

    return $this;
  }

  /**
  * Flush session content data.
  */
  public function flush() : SessionItem
  {
    $this->replace([]);

    return $this;
  }

  /**
  * Refresh the session id and tokens.
  */
  public function refresh($strict = false) : SessionItem
  {
    $this->rebuild($strict);
    $this->generateCsrf();

    return $this;
  }

  /**
  * Destrox the session completly.
  */
  public function destroy() : SessionItem
  {
    $this->flush();
    $this->rebuild(true);

    return $this;
  }

  /**
  * Rebuild session ID.
  */
  public function rebuild($strict = false) : SessionItem
  {
    if ($strict) {
      $this->driver->destroy($this->id);
    }

    $this->setId();

    return $this;
  }

  /**
  * Get the session driver.
  */
  public function driver() :? SessionDriverInterface
  {
    return $this->driver;
  }

  /**
  * Check if session has been started.
  */
  public function started() : bool
  {
    return $this->status;
  }

  /**
  * Return serialized session content.
  */
  public function content() : string
  {
    return serialize($this->all());
  }

  /**
  * Return session name.
  */
  public function name() :? string
  {
    return $this->name;
  }

  /**
  * Set session name.
  */
  public function setName(string $name) : SessionItem
  {
    $this->name = $name;

    return $this;
  }

    /**
  * Get session id.
  */
  public function id() :? string
  {
    return $this->id;
  }

  /**
  * Set session id.
  */
  public function setId(?string $id = null) : SessionItem
  {
    $this->id = $this->generateID($id);

    return $this;
  }

  /**
  * Generate valid session ID.
  */
  private function generateID(?string $id) : string
  {
    if (is_string($id) &&  $this->idLength === strlen($id)
        && Str::hay($id)->alnum()) {

      return $id;
    }

    return KeyGenerator::generateAlphanumeric($this->idLength);
  }

  /**
  * Get session csrf token.
  */
  public function csrf() :? string
  {
    return $this->get(self::CSRF_SESSION_KEY);
  }

  /**
  * Generate session csrf token.
  */
  public function generateCsrf(string $key = null) : void
  {
    $this->set(self::CSRF_SESSION_KEY, ($key ?? Keygenerator::generateSecureKey($this->csrfLength)));
  }

  /**
  * Get previous url.
  */
  public function previous() :? string
  {
    return $this->get('_previous.url');
  }

  /**
  * Set current url to be accessible
  */
  public function setPreviousUrl(string $url) : SessionItem
  {
    $this->set('_previous.url', $url);

    return $this;
  }

  /**
  * Get intended url.
  */
  public function intended() :? string
  {
    return $this->get('_intended.url');
  }

  /**
  * Set intended url to be accessible.
  */
  public function setIntendedUrl(string $url) : SessionItem
  {
    $this->set('_intended.url', $url);

    return $this;
  }

  /**
  * Get session error data.
  */
  public function getErrors() : mixed
  {
    return $this->get('_errors');
  }

  /**
  * Set flash error for next request.
  */
  public function errors(mixed $messages) : SessionItem
  {
    return $this->flash('_errors', $messages);
  }

  /**
  * Get session input data.
  */
  public function getInput(string $key = null) : mixed
  {
    return $key && !is_null($this->get('_input')) 
      ? $this->get('_input')[$key] 
      : $this->get('_input');
  }

  /**
  * Set flash input for next request.
  */
  public function input(array $inputs) : SessionItem
  {
    return $this->flash('_input', $inputs);
  }

  /**
  * Get session flash data.
  */
  public function getFlash() :? array
  {
    return $this->get('_flash.prev');
  }

  /**
  * Set flash message for next request.
  */
  public function flash(string $key, $value = null) : SessionItem
  {
    $this->set($key, $value);
    $this->push('_flash.next', $key);

    return $this;
  }

  /**
  * Set flash message for current request.
  */
  public function flashnow(string $key, $value = null) : SessionItem
  {
    $this->set($key, $value);
    $this->push('_flash.prev', $key);

    return $this;
  }

  /**
  * Remove flash message(s).
  */
  public function deflash(...$keys) : SessionItem
  {
    $difference = array_diff($this->get('_flash.prev', []), func_get_args());
    $this->set('_flash.prev', $difference);

    array_walk(func_get_args(), function($value, $key) {
      $this->delete($key);
    });

    return $this;
  }

  /**
  * Clear flash message data if it has been used.
  */
  public function cleanFlash() : SessionItem
  {
    $difference = array_diff($this->get('_flash.prev', []), $this->get('_flash.next', []));

    array_walk($difference, function($key) {
      $this->delete($key);
    });

    return $this;
  }

  /**
  * Persist flash message through next request.
  */
  public function persistFlash(...$keys) : SessionItem
  {
    $flashes = Arr::hay($this->get('_flash.next', []))->unique(func_get_args());
    $difference = array_diff($this->get('_flash.prev', []), func_get_args());

    $this->set('_flash.next', $flashes);
    $this->set('_flash.prev', $difference);

    return $this;
  }

  /**
  * Prepare Flash data for new request.
  */
  public function prepareFlash() : SessionItem
  {
    $this->cleanFlash();
    $this->set('_flash.prev', $this->get('_flash.next', []));
    $this->set('_flash.next', []);

    return $this;
  }

}
