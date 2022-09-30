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

use Twipsi\Support\Bags\RecursiveArrayBag as Container;
use Twipsi\Support\Arr;

class CacheControl extends Container implements \Stringable
{
  /**
  * Cache directive constructor.
  */
  public function __construct(string $header = null)
  {
    $header = null !== $header
              ? $this->extractCacheDirectives($header)
              : [];

    parent::__construct($header);
  }

  /**
  * Extract cache controll directives.
  */
  public function extractCacheDirectives(string $header)
  {
    $directives = Arr::hay([$header])->separate(',');
    $this->replace(Arr::hay($directives)->pair('='));
  }

  /**
  * Set chache control to private.
  * The private response directive indicates that the response can be stored
  * only in a private cache (e.g. local caches in browsers).
  */
  public function private() : CacheControl
  {
    $this->delete('public');
    $this->set('private', true);

    return $this;
  }

  /**
  * Set chache control to public.
  * Responses for requests with Authorization header fields must not be
  * stored in a shared cache. But the public directive will cause such
  * responses to be stored in a shared cache.
  */
  public function public() : CacheControl
  {
    $this->delete('private');
    $this->set('public', true);

    return $this;
  }

  /**
  * Set cache control no-store.
  * The no-store response directive indicates that any caches of any kind
  * (private or shared) should not store this response.
  */
  public function store(bool $state = true) : CacheControl
  {
    if (!$state) {
      $this->set('no-store', true );
    } else {
      $this->delete('no-store');
    }

    return $this;
  }

  /**
  * Set cache control must-revalidate.
  * The no-cache response directive indicates that the response can be stored in
  * caches, but must be validated with the origin server before each
  * reuse — even when the cache is disconnected from the origin server.
  */
  public function cache(bool $state = true) : CacheControl
  {
    if (!$state) {
      $this->set('no-cache', true );
    } else {
      $this->delete('no-cache');
    }

    return $this;
  }

  /**
  * Set cache control must-revalidate.
  * The must-revalidate response directive indicates that the response can be
  * stored in caches and can be reused while fresh. Once it becomes stale,
  * it must be validated with the origin server before reuse.
  */
  public function revalidate(bool $state = true) : CacheControl
  {
    if ($state) {
      $this->set('must-revalidate', true );
      $this->set('proxy-revalidate', true );

      if (! $this->has('max-age')) {
        $this->maxAge(604800);
      }

    } else {
      $this->delete('must-revalidate');
      $this->delete('proxy-revalidate');
    }

    return $this;
  }

  /**
  * Set cache control must-understand.
  * The must-understand response directive indicates that a cache should store
  * the response only if it understands the requirements for caching based on status code.
  */
  public function understand(bool $state = true) : CacheControl
  {
    if ($state) {
      $this->set('must-understand', true );
      $this->store(false);

    } else {
      $this->delete('must-understand');
      $this->store(true);
    }

    return $this;
  }

  /**
  * Set cache control no-transform.
  * no-transform indicates that any intermediary (regardless of whether it implements a cache)
  * shouldn't transform the response contents.
  */
  public function transform(bool $state = true) : CacheControl
  {
    if (!$state) {
      $this->set('no-transform', true );
    } else {
      $this->delete('no-transform');
    }

    return $this;
  }

  /**
  * Set cache control to immutable.
  * The immutable response directive indicates that the response
  * will not be updated while it's fresh.
  */
  public function immutable(bool $state = true) : CacheControl
  {
    if ($state) {
      $this->set('immutable', true );
    } else {
      $this->delete('immutable');
    }

    return $this;
  }

  /**
  * Set cache control max-age.
  * The max-age=N response directive indicates that the response remains fresh
  * until N seconds after the response is generated.
  */
  public function maxAge(int $seconds) : CacheControl
  {
    $this->set('max-age', $seconds );

    return $this;
  }

  /**
  * Set cache control s-maxage.
  * The s-maxage response directive also indicates how long the response is
  * fresh for (similar to max-age) — but it is specific to shared caches,
  * and they will ignore max-age when it is present.
  */
  public function sharedMaxAge(int $seconds) : CacheControl
  {
    $this->public();
    $this->set('s-maxage', $seconds );

    return $this;
  }

  /**
  * Return cache controll compiled as a string.
  */
  public function __toString() : string
  {
    ksort($this->parameters);

    if (empty($this->all())) {
      return '';
    }

    foreach($this as $header => $value) {
      $dir[] = !is_bool($value) ? $header.'='.$value : $header;
    }

    return implode(', ', $dir ?? []);
  }

}
