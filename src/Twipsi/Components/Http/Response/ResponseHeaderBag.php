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

namespace Twipsi\Components\Http\Response;

use Twipsi\Components\Cookie\Cookie;
use Twipsi\Support\Bags\ArrayBag;
use Twipsi\Components\Http\CacheControl;
use Twipsi\Components\Http\HeaderBag;
use Twipsi\Support\Chronos;
use Twipsi\Support\Str;

class ResponseHeaderBag extends HeaderBag
{
  /**
  * List of headers that should reform the cache control directive.
  */
  protected const CACHE_CONTROL_REFORMABLE_HEADERS = [
    'cache-control',
    'etag',
    'last-modified',
    'expires'
  ];

  /**
  * Header bag constructor.
  */
  public function __construct(array $headers = [])
  {
    parent::__construct($headers);

    if (! $this->has('cache-control')) {
      $this->cacheControl->cache(false)->private();
    }

    if (! $this->has('date')) {
      $this->createStamp();
    }
  }

  /**
  * Create the Date header witha  valid time stamp.
  */
  private function createStamp() : void
  {
    $date = Chronos::date()
            //->setTimezone(Config::get('system.timezone', 'UTC'))
            ->setDateTimeFormat('D, d M Y H:i:s')
            ->getDateTime();

    $this->set('Date', $date.' GMT');
  }

  /**
  * Return all headers with exceptions converted to friendly case.
  */
  public function all(string ...$exceptions) : array
  {
    if (! empty(func_get_args())) {

      foreach (func_get_args() as &$key) {
        $key = Str::hay($key)->header();
      }
    }

    $headers = parent::all(...$exceptions);

    if (! in_array('set-cookie', $exceptions)) {
      $headers['set-cookie'] = $this->getCookies();
    }

    return $headers;
  }

  /**
  * Return all headers with exceptions camelized.
  */
  public function camelize() : array
  {
    $headers = $this->all();

    foreach( $headers as $header => $value ) {
      $camelized[Str::hay($header)->camelize('-')] = $value;
    }

    return $camelized ?? [];
  }

  /**
  * Replace headers converted to friendly case.
  */
  public function replace(array|ArrayBag $parameters) : static
  {
    parent::replace($parameters);

    if (! $this->has('date')) {
      $this->createStamp();
    }

    return $this;
  }

  /**
  * Merge headers converted to friendly case.
  */
  public function merge(array|ArrayBag $parameters) : static
  {
    parent::inject($parameters);

    if (! $this->has('date')) {
      $this->createStamp();
    }

    return $this;
  }

  /**
  * Set a response cookie header.
  */
  public function setCookie(Cookie|string $cookie) : static
  {
    $cookies = $this->get('set-cookie', []);

    if (! $cookie instanceof Cookie) {
      $cookie = Cookie::fromString($cookie);
    }

    $cookies[$cookie->getName()] = $cookie;

    return $this->set('set-cookie', $cookies);
  }

  /**
  * Set a header key/value pair converted to friendly case.
  */
  public function set(string $key, mixed $value, bool $recursive = true) : static
  {
    parent::set($key, $value, $recursive);

    if (in_array(Str::hay($key)->header(), self::CACHE_CONTROL_REFORMABLE_HEADERS)) {
      $this->reformCacheControl();
    }

    return $this;
  }

  /**
  * Get response cookie header(s).
  */
  public function getCookies(string $name = null) : mixed
  {
    $cookies = $this->get('set-cookie', []);

    if (null !== $name) {
      return $cookies[$name] ?? null;
    }

    return $cookies;
  }

  /**
  * Delete a response cookie.
  */
  public function deleteCookie(string $name) : static
  {
    $cookies = $this->get('set-cookie', []);
    unset($cookies[$name]);

    return empty($cookies) ? $this->delete('set-cookie') : $this->set('set-cookie', $cookies);
  }

  /**
  * Expire a cookie that is alive.
  */
  public function expireCookie(string $name) : static
  {
    return $this->setCookie(new Cookie($name, null, 1));
  }

  /**
  * Reform cache controll depending on set headers.
  */
  protected function reformCacheControl() : CacheControl
  {
    if (empty($this->cacheControl)) {

      if ($this->has('last-modified') || $this->has('expires')) {
        return $this->cacheControl->private()->revalidate(); //(RFC 7234 Section 4.2.2) in the case of "Last-Modified".
      }

      return $this->cacheControl->cache(false)->private(); // Default cache control.
    }

    if ( !$this->cacheControl->has('s-maxage')) {
      return $this->cacheControl->private();
    }

    return $this->cacheControl;
  }

}
