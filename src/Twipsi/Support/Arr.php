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

namespace Twipsi\Support;

use Closure;

class Arr
{
    public function __construct(protected array $haystack)
    {
    }

    /**
     * Initialize the Arr object staticly.
     *
     * @param array|string $array
     *
     * @return Arr
     */
    public static function hay(array|string $array): Arr
    {
        return new self(is_string($array) ? [$array] : $array);
    }

    /**
     * Check if array key exists.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->haystack);
    }

    /**
     * Check if array value exists.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return \in_array($value, $this->haystack);
    }

    /**
     * Find all specific keys in a (multidim) array.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public function find(string|array $key): bool
    {
        if (is_string($key)) {
            return !empty(array_column($this->haystack, $key));
        }

        foreach ($key as $needle) {
            if (empty(array_column($this->haystack, $needle))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find any one specific key in a (multidim) array.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public function findAny(string|array $key): bool
    {
        if (is_string($key)) {
            return !empty(\array_column($this->haystack, $key));
        }

        foreach ($key as $needle) {
            if (!empty(\array_column($this->haystack, $needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find all specific values in a (multidim) array.
     *
     * @param string|array $values
     *
     * @return bool
     */
    public function search(string|array $values): bool
    {
        if (is_string($values)) {
            return false !== array_search($values, $this->haystack);
        }

        foreach ($values as $value) {
            if (false === array_search($value, $this->haystack)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find any one specific value in a (multidim) array.
     *
     * @param string|array $values
     * @return bool
     */
    public function searchAny(string|array $values): bool
    {
        if (is_string($values)) {
            return false !== array_search($values, $this->haystack);
        }

        foreach ($values as $value) {
            if (false !== array_search($value, $this->haystack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return merged and unique result.
     *
     * @param array|null $array
     *
     * @return array
     */
    public function unique(array $array = null): array
    {
        if (!is_null($array)) {
            $this->haystack = array_merge($this->haystack, $array);
        }

        return array_unique($this->haystack);
    }

    /**
     * Pair items split by a separator.
     *
     * @param string|array $separators
     * @return array
     */
    public function pair(string|array $separators): array
    {
        foreach ($this->haystack as $string) {

            // If we only have one separator.
            if(is_string($separators) && !empty($separators)) {
                $parts = explode($separators, $string, 2);

            } else {
                foreach ($separators as $separator) {

                    $parts = explode($separator, $string, 2);
                    if(isset($parts[1])) { break;}
                }
            }

            $keys[] = isset($parts[0]) ? trim($parts[0]) : $string;
            $values[] = isset($parts[1]) ? trim($parts[1]) : true;
        }

        return isset($keys, $values) ? array_combine($keys, $values) : [];
    }

    /**
     * Separate items based on seperation characters.
     *
     * @param string $separators
     *
     * @return array
     */
    public function separate(string $separators): array
    {
        foreach ($this->haystack as $string) {
            $token = strtok($string, $separators);

            while (false !== $token) {
                $parts[] = trim($token);
                $token = strtok($separators);
            }
        }

        return $parts ?? [];
    }

    /**
     * Flatten a multidim array.
     *
     * @return array
     */
    public function flatten(): array
    {
        $return = [];

        array_walk_recursive($this->haystack, function ($v, $k) use (&$return) {

            if(isset($return[$k])) {
                $return[] = $v;

            } else {
                $return[$k] = $v;
            }
        });

        return $return;
    }

    /**
     * Loop through an array with a closure until we recieve a return.
     *
     * @param Closure $closure
     *
     * @return mixed
     */
    public function attempt(Closure $closure): mixed
    {
        foreach ($this->haystack as $value) {
            if (null !== ($result = $closure($value))) {
                return $result;
            }
        }

        return false;
    }

    /**
     * Separate items with a  closure.
     * 
     * @param Closure $callback
     * 
     * @return array
     */
    public function distinguish(Closure $callback): array 
    {
        $separated = array_filter($this->haystack, $callback);

        return [$separated, array_diff($this->haystack, $separated)];
    }

    /**
     * Return all keys except...
     *
     * @param string ...$keys
     *
     * @return array
     */
    public function except(string ...$keys): array
    {
        $filtered = array_filter($this->haystack, function ($k) use ($keys) {
            return ! in_array($k, $keys);
        }, ARRAY_FILTER_USE_KEY);

        return $filtered;
    }

    /**
     * Return only the selected keys.
     *
     * @param string ...$keys
     *
     * @return array
     */
    public function only(string ...$keys): array
    {
        $filtered = array_filter($this->haystack, function ($k) use ($keys) {
            return in_array($k, $keys);
        }, ARRAY_FILTER_USE_KEY);

        return $filtered;
    }

    /**
     * Gather all the values where key is...
     *
     * @param string $key
     *
     * @return array
     */
    public function gather(string $key): array
    {
        return array_map(function ($k) use ($key) {
            return $k[$key] ?? null;
        }, $this->haystack);
    }

    /**
     * Set data recusrively into an array seperated by (.)
     *
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    public function set(string $key, mixed $value): array
    {
        if (Str::hay($key)->contains('.')) {
            $sections = explode('.', $key);

            $array = &$this->haystack;

            while (count($sections) > 1) {
                $parent = array_shift($sections);

                if (! isset($array[$parent]) || ! is_array($array[$parent])) {
                    $array[$parent] = [];
                }

                $array = &$array[$parent];
            }

            $array[array_shift($sections)] = $value;
        } else {
            $this->haystack[$key] = $value;
        }

        return $this->haystack;
    }

    /**
     * Set data recusrively into an array seperated by (.)
     *
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    public function push(string $key, mixed $value): array
    {
        if (Str::hay($key)->contains('.')) {
            $sections = explode('.', $key);

            $array = &$this->haystack;

            while (count($sections) > 1) {
                $parent = array_shift($sections);

                if (! isset($array[$parent]) || ! is_array($array[$parent])) {
                    $array[$parent] = [];
                }

                $array = &$array[$parent];
            }

            $array[array_shift($sections)][] = $value;
        } else {
            $this->haystack[$key][] = $value;
        }

        return $this->haystack;
    }

    /**
     * Set data recursively from an array seperated by (.)
     *
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (Str::hay($key)->contains('.')) {
            $sections = explode('.', $key);
            $parent = array_shift($sections);

            $entry = array_reduce($sections, function ($carry, $item) {
                if (is_array($carry)) {
                    return isset($carry[$item]) ? $carry[$item] : null;
                }

                return $carry;
            }, $this->get($parent));

            return ! is_null($entry) ? $entry : $default;
        }

        return isset($this->haystack[$key]) ? $this->haystack[$key] : $default;
    }

    /**
     * Delete data recursively from an array seperated by (.)
     *
     * @param string $key
     *
     * @return array
     */
    public function delete(string $key): array
    {
        if (Str::hay($key)->contains('.')) {
            $sections = explode('.', $key);

            $array = &$this->haystack;

            while (count($sections) > 1) {
                $parent = array_shift($sections);

                if (! isset($array[$parent]) || ! is_array($array[$parent])) {
                    $array[$parent] = [];
                }

                $array = &$array[$parent];
            }

            unset($array[array_shift($sections)]);
        } else {
            unset($this->haystack[$key]);
        }

        return $this->haystack;
    }
}
