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

namespace Twipsi\Components\Http;

use Twipsi\Support\Bags\ArrayBag as Container;
use Twipsi\Components\Http\CacheControl;
use Twipsi\Support\Str;

class HeaderBag extends Container implements \Stringable
{
  /**
  * Cache controll directives storage.
  */
  protected CacheControl $cacheControl;

  /**
  * Headerbag constructor.
  */
  public function __construct(array $headers = [])
  {
    parent::__construct();
    $this->cacheControl = new CacheControl;

    foreach ($headers as $key => $value) {
      $this->set($key, $value);
    }
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

    if (! in_array('cache-control', $exceptions)) {
      $headers['cache-control'] = (string)$this->cacheControl;
    }

    return $headers;
  }

  /**
  * Return selected headers converted to friendly case.
  */
  public function selected(string ...$keys) : array
  {
    if (! empty(func_get_args())) {

      foreach (func_get_args() as &$key) {
        $key = Str::hay($key)->header();
      }
    }

    $headers = parent::selected(...$keys);

    if (! in_array('cache-control', $keys)) {
      $headers['cache-control'] = (string)$this->cacheControl;
    }

    return $headers;
  }

  /**
  * Replace headers converted to friendly case.
  */
  public function replace(array|Container $parameters) : static
  {
    if ($parameters instanceof Container) {
      $parameters = $parameters->all();
    }

    $parameters = \array_filter($parameters, function($k) {
      return $k = Str::hay($k)->header();
    }, ARRAY_FILTER_USE_KEY);

    if (array_key_exists('cache-control', $parameters)) {
      $this->cacheControl->extractCacheDirectives($parameters['cache-control']);
    }

    return parent::replace($parameters);
  }

  /**
  * Merge headers converted to friendly case.
  */
  public function merge(array|Container $parameters) : static
  {
    if ($parameters instanceof Container) {
      $parameters = $parameters->all();
    }

    $parameters = \array_filter($parameters, function($k) {
      return $k = Str::hay($k)->header();
    }, ARRAY_FILTER_USE_KEY);

    if (array_key_exists('cache-control', $parameters)) {
      $this->cacheControl->extractCacheDirectives($parameters['cache-control']);
    }

    return parent::merge($parameters);
  }

  /**
  * Set a header key/value pair converted to friendly case.
  */
  public function set(string $key, mixed $value) : static
  {
    if ('cache-control' === $key = Str::hay($key)->header()) {
      $this->cacheControl->extractCacheDirectives($value);
    }

    return parent::set($key, $value);
  }

  /**
  * Get a header value based on header name.
  */
  public function get(string $key, mixed $default = null) : mixed
  {
    if ('cache-control' === $key = Str::hay($key)->header()) {
      return (string) $this->cacheControl;
    }

    if (! empty($header = parent::get($key, $default))) {
      return $header;
    }

    if (! strpos($key, 'http-')) {
      $key = 'http-' . $key;
    }

    return parent::get($key, $default);
  }

  /**
  * Check if a header exists.
  */
  public function has(string $key) : bool
  {
    if ('cache-control' === $key = Str::hay($key)->header()) {
      return !empty($this->cacheControl->all());
    }

    return parent::has($key);
  }

  /**
  * Delete a header.
  */
  public function delete(string $key) : static
  {
    if ('cache-control' === $key = Str::hay($key)->header()) {
      $this->cacheControl->replace([]);
    }

    return parent::delete($key);
  }

  /**
  * Find first occurance with value.
  */
  public function find(...$order) :? string
  {
    foreach ($order as $key) {

      if (! empty($result = $this->get($key))) {
        return $result;
      }
    }

    return null;
  }

  /**
  * Return cache control directive container.
  */
  public function cacheControl() : CacheControl
  {
    return $this->cacheControl;
  }

  /**
  * Return headers compiled as a string.
  */
  public function __toString() : string
  {
    if(empty($headers = $this->all())) {
      return '';
    }

    ksort($headers);

    foreach($headers as $header => $values) {
      $values = is_array($values) ? $values : [$values];

      foreach( $values as $value ) {
        $header = ucwords($header, '-');
        $content[] = sprintf("%s %s\r\n", $header.':', $value);
      }
    }

    return implode(' ', $content ?? []);
  }

}
