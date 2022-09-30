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

use \RuntimeException;
use Twipsi\Components\Session\SessionItem;
use Twipsi\Components\Session\SessionSubscriber;
use Twipsi\Support\Applicables\Configuration;

class SessionHandler
{
  use Configuration;

  /**
  * Construct session handler
  */
  public function __construct(protected SessionSubscriber $subscriber){}

  /**
  * Create and Access a specific session driver.
  */
  public function driver(string $driver = null) : SessionItem
  {
    if (! $driver = ($driver ?? $this->defaultDriver())) {
      throw new RuntimeException('No session driver has been configured');
    }

    if (! $this->subscriber->drivers->has($driver)) {
      $this->subscriber->create($driver);
    }

    return $this->subscriber->drivers->get($driver);
  }

  /**
  * Get configured default driver.
  */
  public function defaultDriver() :? string
  {
    return $this->config->get('session.driver');
  }

  /**
  * Check if we have a default driver configured.
  */
  public function isConfigured() : bool
  {
    return false === !$this->config->get('session.driver');
  }

}
