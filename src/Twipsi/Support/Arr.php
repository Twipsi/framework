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
use InvalidArgumentException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Arr
{
    /**
     * Create array parents based on dotted key.
     *
     * @param array $array
     * @param string|int $key
     * @return array
     */
    public static function populate(array $array, string|int $key): array
    {
        $sections = explode('.', $key);
        $haystack = &$array;

        while (count($sections) > 1) {
            $parent = array_shift($sections);

            if (! isset($haystack[$parent]) || ! is_array($haystack[$parent])) {
                $haystack[$parent] = [];
            }

            $haystack = &$haystack[$parent];
        }

        return $array;
    }

    /**
     * Set data recursively into an array seperated by (.)
     *
     * @param array $haystack
     * @param string|int $key
     * @param mixed $value
     * @param bool $recursive
     * @return array
     */
    public static function set(array $haystack, string|int $key, mixed $value, bool $recursive = true): array
    {
        // If we have a dot notation check if the array is recursive,
        // otherwise just attempt to find a key with the full name.
        if (static::shouldHandleRecursive($haystack, $key) && $recursive) {

            $haystack = static::populate($haystack, $key);

            $parent = &$haystack;
            foreach(explode('.', $key) as $section) {
                $parent = &$parent[$section];
            }
            $parent = $value;

        } else {
            $haystack[$key] = $value;
        }

        return $haystack;
    }

    /**
     * Set data recursively into an array seperated by (.)
     *
     * @param array $haystack
     * @param string|int $key
     * @param mixed $value
     * @param bool $recursive
     * @return array
     */
    public static function put(array $haystack, string|int $key, mixed $value, bool $recursive = true): array
    {
        return static::set($haystack, $key, $value, $recursive);
    }

    /**
     * Prepend parameter to the front of the array by key also.
     *
     * @param array $haystack
     * @param mixed $value
     * @param string|null|int $key
     * @param bool $recursive
     * @return array
     */
    public static function prepend(array $haystack, mixed $value, string|int $key = null, bool $recursive = true): array
    {
        // If we have a dot notation check if the array is recursive,
        // otherwise just attempt to find a key with the full name.
        if (!is_null($key) && static::shouldHandleRecursive($haystack, $key) && $recursive) {

            $parent = &$haystack;
            foreach(explode('.', $key) as $section) {
                $parent = &$parent[$section];
            }

            if(is_null($parent)) {
                throw new InvalidArgumentException(
                    sprintf("key [%s] could not be found in the haystack", $key)
                );
            }

            array_unshift($parent, $value);

        } else {
            is_null($key)
                ? array_unshift($haystack, $value)
                : $haystack = [$key => $value]+$haystack;
        }

        return $haystack;
    }

    /**
     * Push data recursively into an array seperated by (.)
     *
     * @param array $haystack
     * @param string $into
     * @param mixed $value
     * @param bool $recursive
     * @return array
     */
    public static function push(array $haystack, string $into, mixed $value, bool $recursive = true): array
    {
        // If we have a dot notation check if the array is recursive,
        // otherwise just attempt to find a key with the full name.
        if (static::shouldHandleRecursive($haystack, $into) && $recursive) {

            $haystack = static::populate($haystack, $into);

            $parent = &$haystack;
            foreach(explode('.', $into) as $section) {
                $parent = &$parent[$section];
            }
            $parent[] = $value;

        } else {
            $haystack[$into][] = $value;
        }

        return $haystack;
    }

    /**
     * Check if all array keys exist recursively by (.)
     *
     * @param array $haystack
     * @param string ...$keys
     * @return bool
     */
    public static function has(array $haystack, string|int ...$keys): bool
    {
        foreach ($keys as $key) {
            if (static::shouldHandleRecursive($haystack, $key)) {

                $sections = explode('.', $key);

                for($i=0; $i < count($sections); $i++) {
                    if(!isset($haystack[$sections[$i]])) {
                        return false;
                    }

                    $haystack = $haystack[$sections[$i]];
                }

            } else {
                if(! array_key_exists($key, $haystack)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if any array keys exist recursively by (.)
     *
     * @param array $haystack
     * @param string ...$keys
     * @return bool
     */
    public static function hasAny(array $haystack, string|int ...$keys): bool
    {
        foreach ($keys as $key) {
            if(static::has($haystack, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find all specific endpoint keys in a recursive array.
     *
     * @param array $haystack
     * @param string ...$keys
     * @return bool
     */
    public static function find(array $haystack, string|int ...$keys): bool
    {
        foreach ($keys as $needle) {

            if(array_key_exists($needle, $haystack)) {
                continue;
            }
            if (empty(array_column($haystack, $needle))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find any one specific endpoint key in a recursive array.
     *
     * @param array $haystack
     * @param string ...$keys
     * @return bool
     */
    public static function findAny(array $haystack, string|int ...$keys): bool
    {
        foreach ($keys as $needle) {

            if(array_key_exists($needle, $haystack)) {
                return true;
            }
            if (!empty(array_column($haystack, $needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a parameter from parameters array recursively by (.)
     *
     * @param array $haystack
     * @param string ...$keys
     * @return array
     */
    public static function delete(array $haystack, string|int ...$keys): array
    {
        foreach($keys as $key) {
            if (static::shouldHandleRecursive($haystack, $key)) {

                $parent = &$haystack;
                $sections = explode('.', $key);

                for($i = 0; $i < (count($sections)-1); $i++) {

                    if(! isset($parent[$sections[$i]])) {
                        break 2;
                    }

                    $parent = &$parent[$sections[$i]];
                }

                unset($parent[end($sections)]);

            } else {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    /**
     * Return all keys except...
     *
     * @param array $haystack
     * @param string ...$keys
     * @return array
     */
    public static function except(array $haystack, string|int ...$keys): array
    {
        return static::delete($haystack, ...$keys);
    }

    /**
     * Reject keys and return remaining using a callback...
     *
     * @param array $haystack
     * @param Closure $callback
     * @return array
     */
    public static function reject(array $haystack, Closure $callback): array
    {
        return static::diff($haystack, static::filter($haystack, $callback));
    }

    /**
     * Return only the selected keys.
     *
     * @param array $haystack
     * @param string ...$keys
     * @return array
     */
    public static function only(array $haystack, string|int ...$keys): array
    {
        if (!func_get_args()) {
            return $haystack;
        }

        return static::filter($haystack, function ($v, $k) use ($keys) {
                return in_array($k, $keys);
            }, true);
    }

    /**
     * Get data recursively from an array seperated by (.)
     *
     * @param array $haystack
     * @param string|int $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function get(array $haystack, string|int $key, mixed $default = null): mixed
    {
        if (static::shouldHandleRecursive($haystack, $key)) {

                $sections = explode('.', $key);
                $parent = array_shift($sections);

                $entry = array_reduce($sections, function ($carry, $item) {
                    if (is_array($carry)) {
                        return $carry[$item] ?? null;
                    }

                    return $carry;
                }, static::get($haystack, $parent));

                return !is_null($entry) ? $entry : $default;
        }

        return $haystack[$key] ?? $default;
    }

    /**
     * Return all values in the recursive array.
     *
     * @param array $haystack
     * @param bool $flatten
     * @return array
     */
    public static function values(array $haystack, bool $flatten = false): array
    {
        if (static::isRecursive($haystack)) {
            foreach ($haystack as $element) {

                $new[] = is_array($element)
                    ? static::values($element)
                    : $element;
            }

            return $flatten
                ? static::flatten($new ?? [])
                : $new ?? [];

        } else {
            return array_values($haystack);
        }
    }

    /**
     * Return all keys in the recursive array.
     *
     * @param array $haystack
     * @param bool $flatten
     * @return array
     */
    public static function keys(array $haystack, bool $flatten = false): array
    {
        if (static::isRecursive($haystack)) {
            foreach ($haystack as $key => $element) {

                $new[] = is_array($element)
                    ? static::keys($element)
                    : $key;
            }

            return $flatten
                ? static::flatten($new ?? [])
                : $new ?? [];

        } else {
            return array_keys($haystack);
        }
    }

    /**
     * Returns the number of parameters in the array or sub array.
     *
     * @param array $haystack
     * @param string|null|int $key
     * @return int
     */
    public static function count(array $haystack, string|int $key = null): int
    {
        if(!is_null($key) && is_array($result = static::get($haystack, $key))) {
            return count($result, COUNT_RECURSIVE);
        }

        return count($haystack, COUNT_RECURSIVE);
    }

    /**
     * Returns the sum of parameter values in the array or sub array.
     *
     * @param array $haystack
     * @param string|null|int $key
     * @return int
     */
    public static function sum(array $haystack, string|int $key = null): int
    {
        $clone = Arr::filter($haystack, fn($v) => is_numeric($v));

        if(!is_null($key)) {
            $clone = is_array($result = static::get($clone, $key))
                ? $result
                : [];
        }

        foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($clone)) as $value) {
            $sum = ($sum ?? 0) + $value;
        }

        return $sum ?? 0;
    }

    /**
     * Returns the avg of parameter values in the array or sub array.
     *
     * @param array $haystack
     * @param string|null|int $key
     * @return float|int
     */
    public static function avg(array $haystack, string|int $key = null): float|int
    {
        $clone = Arr::filter($haystack, fn($v) => is_numeric($v));

        if(!is_null($key)) {
            $clone = is_array($result = static::get($clone, $key))
                ? $result
                : [];
        }

        foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($clone)) as $value) {
            $sum[] = $value;
        }

        $division = count($sum ?? []);
        return array_sum($sum ?? [])/($division > 0 ? $division : 1);
    }

    /**
     * Returns the min value in the array or sub array.
     *
     * @param array $haystack
     * @param string|null|int $key
     * @return mixed
     */
    public static function min(array $haystack, string|int $key = null): mixed
    {
        if(!is_null($key)) {
            $haystack = is_array($result = static::get($haystack, $key))
                ? $result
                : [];
        }

        foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack)) as $value) {
            $values[] = $value;
        }

        return min($values ?? []);
    }

    /**
     * Returns the max value in the array or sub array.
     *
     * @param array $haystack
     * @param string|null|int $key
     * @return mixed
     */
    public static function max(array $haystack, string|int $key = null): mixed
    {
        if(!is_null($key)) {
            $haystack = is_array($result = static::get($haystack, $key))
                ? $result
                : [];
        }

        foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack)) as $value) {
            $values[] = $value;
        }

        return max($values ?? []);
    }

    /**
     * Returns the first parameter in a recursive array.
     *
     * @param array $haystack
     * @param Closure|null $callback
     * @param bool $flatten
     * @return mixed
     */
    public static function first(array $haystack, Closure $callback = null, bool $flatten = false): mixed
    {
        if($callback instanceof Closure) {
            $filter = static::filter($haystack, $callback, true, !$flatten);
        }

        $first = $flatten
            ? static::flatten($filter ?? $haystack)
            : $filter ?? $haystack;

        return reset($first);
    }

    /**
     * Get the first where key is key and value is value.
     *
     * @param array $haystack
     * @param string|int $key
     * @param string|null $operator
     * @param mixed|null $value
     * @param bool $reverse
     * @return mixed
     */
    public static function firstWhere(array $haystack, string|int $key, string $operator = null, mixed $value = null, bool $reverse = false): mixed
    {
        $collection = $reverse ? array_reverse($haystack) : $haystack;

        foreach($collection as $property => $v) {
            if(is_array($v)) {
                if($result = static::firstWhere($v, $key, $operator, $value)) {
                    return $result;
                }
            }

            // If the key doesn't match continue.
            if($key !== $property) {
                continue;
            }

            // If there is only a key set return first non-null or false value.
            if(is_null($operator) && !is_null($v) && $v !== false) {
                return $haystack;
            }

            // If we did-int provide an operator for easy syntax then convert them.
            if(!is_null($operator) && is_null($value)) {
                [$value, $operator] = [$operator, '='];
            }

            if(($operator === '=' && $value === $v) || ($operator === '!=' && $value !== $v)
                || ($operator === '>' && $v > $value) || ($operator === '>=' && $v >= $value)
                || ($operator === '<' && $v < $value) || ($operator === '<=' && $v <= $value)) {
                return $haystack;
            }
        }

        return false;
    }

    /**
     * Returns the last parameter in a recursive array.
     *
     * @param array $haystack
     * @param Closure|null $callback
     * @param bool $flatten
     * @return mixed
     */
    public static function last(array $haystack, Closure $callback = null, bool $flatten = false): mixed
    {
        if($callback instanceof Closure) {
            $filter = static::filter($haystack, $callback, true, !$flatten);
        }

        $last = $flatten
            ? array_reverse(static::flatten($filter ?? $haystack))
            : array_reverse($filter ?? $haystack);

        return reset($last);
    }

    /**
     * Get the last where key is key and value is value.
     *
     * @param array $haystack
     * @param string|int $key
     * @param string|null $operator
     * @param mixed|null $value
     * @return mixed
     */
    public static function lastWhere(array $haystack, string|int $key, string $operator = null, mixed $value = null): mixed
    {
        return static::firstWhere($haystack, $key, $operator, $value, true);
    }

    /**
     * Find a value in a recursive array and return the key.
     *
     * @param array $haystack
     * @param string $value
     * @param bool $strict
     * @return mixed
     */
    public static function search(array $haystack, mixed $value, bool $strict = false): mixed
    {
        if (static::isRecursive($haystack)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveArrayIterator($haystack), RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $key => $element) {
                if (($strict && $element === $value) || (!$strict && $element == $value)) {

                    if (($depth = $iterator->getDepth() - 1) < 0) {
                        return $key;
                    }

                    while ($depth >= 0) {
                        $key = $iterator->getSubIterator($depth)->key() . '.' . $key;
                        $depth--;
                    }

                    return $key;
                }
            }

            return false;
        } else {
            return array_search($value, $haystack, $strict);
        }
    }

    /**
     * Find all specific values in a recursive array.
     *
     * @param array $haystack
     * @param mixed ...$values
     * @return bool
     */
    public static function exists(array $haystack, mixed ...$values): bool
    {
        foreach ($values as $value) {
            if (! static::search($haystack, $value, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find any specific values in a recursive array.
     *
     * @param array $haystack
     * @param mixed ...$values
     * @return bool
     */
    public static function existsAny(array $haystack, mixed ...$values): bool
    {
        foreach ($values as $value) {
            if (static::search($haystack, $value, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return merged and unique result of a recursive array.
     *
     * @param array $haystack
     * @param array|null $merge
     * @return array
     */
    public static function unique(array $haystack, array $merge = null): array
    {
        if (!is_null($merge)) {

            $haystack = static::isRecursive($haystack)
                ? array_merge_recursive($haystack, $merge)
                : array_merge($haystack, $merge);
        }

        return array_intersect_key($haystack,
            array_unique(array_map('serialize', $haystack))
        );
    }

    /**
     * Loop through the collection executing a callback on all elements.
     *
     * @param array $haystack
     * @param Closure $callback
     * @return array
     */
    public static function loop(array $haystack, Closure $callback): array
    {
        foreach($haystack as $key => &$value) {
            is_array($value)
                ? $value = static::loop($value, $callback)
                : $callback($value, $key);
        }

        return $haystack;
    }

    /**
     * Loop through the collection checking if all elements pass the callback logics.
     *
     * @param array $haystack
     * @param Closure $callback
     * @return bool
     */
    public static function every(array $haystack, Closure $callback): bool
    {
        return static::filter($haystack, $callback)
            === $haystack;
    }

    /**
     * Loop through an array with a closure until we receive a return.
     *
     * @param array $haystack
     * @param Closure $closure
     * @return mixed
     */
    public static function attempt(array $haystack, Closure $closure): mixed
    {
        foreach ($haystack as $key => $value) {

            if(is_array($value)) {
                if($result = static::attempt($value, $closure)) {
                    return $result;
                }
            }

            else if (! is_null($result = $closure($value, $key))) {
                return $result;
            }
        }

        return false;
    }

    /**
     * Array map the collection recursively.
     *
     * @param array $haystack
     * @param Closure $callback
     * @return array
     */
    public static function map(array $haystack, Closure $callback): array
    {
        foreach ($haystack as $key => $value) {
            $result[$key] = is_array($value)
                ? static::map($value, $callback)
                : $callback($value, $key);
        }

        return $result ?? [];
    }

    /**
     * Map using a callback and replacing key/value pair.
     *
     * @param array $haystack
     * @param Closure $callback
     * @return array
     */
    public static function mapPair(array $haystack, Closure $callback): array
    {
        foreach ($haystack as $key => $value) {

            if(is_array($value)) {
                $result = array_merge($result ?? [],
                    static::mapPair($value, $callback)
                );
            }
            else if(is_array($entry = $callback($value, $key))) {
                $result[array_key_first($entry)] = reset($entry);
            }
        }

        return $result ?? [];
    }

    /**
     * Filter a recursive array based on a callback.
     *
     * @param array $haystack
     * @param Closure $callback
     * @param bool $removeEmpty
     * @param bool $filterParent
     * @return array
     */
    public static function filter(array $haystack, Closure $callback, bool $removeEmpty = false, bool $filterParent = false): array
    {
        foreach ($haystack as $key => &$element) {

            $o_filter = array_filter([$key => $element], $callback, ARRAY_FILTER_USE_BOTH);
            if(! empty($o_filter) && !$filterParent) {
                continue;
            }

            if(is_array($element)) {
                $element = static::filter($element, $callback, $removeEmpty, $filterParent);

                if($removeEmpty && empty($element)) {
                    unset($haystack[$key]);
                }

            } elseif ([$key => $element] !== $o_filter) {
                unset($haystack[$key]);
            }
        }

        return $filterParent
            ? array_filter($haystack, $callback, ARRAY_FILTER_USE_BOTH)
            : $haystack;
    }

    /**
     * Get the difference of 2 recursive collections.
     *
     * @param array $haystack
     * @param array $difference
     * @return array
     */
    public static function diff(array $haystack, array $difference): array
    {
        if (static::isRecursive($difference)) {
            foreach ($haystack as $key => $element) {

                if (array_key_exists($key, $difference) && is_array($element)) {
                    $r_diff = static::diff($element, $difference[$key]);
                    !$r_diff ?: $o_diff[$key] = $r_diff;
                }
                else if (! in_array($element, $difference, true)) {
                    $o_diff[$key] = $element;
                }
            }

            return $o_diff ?? [];
        }

        return array_diff($haystack, $difference);
    }

    /**
     * Get the difference of 2 recursive collections based on keys.
     *
     * @param array $haystack
     * @param array $difference
     * @return array
     */
    public static function diffKey(array $haystack, array $difference): array
    {
        if (static::isRecursive($difference)) {
            foreach ($haystack as $key => $element) {

                if (array_key_exists($key, $difference) && is_array($element)) {
                    $r_diff = static::diffKey($element, $difference[$key]);
                    !$r_diff ?: $o_diff[$key] = $r_diff;
                }
                else if (!isset($o_diff[$key]) && !in_array($key, $difference, true)) {
                    $o_diff[$key] = $element;
                }
            }

            return $o_diff ?? [];
        }

        return array_diff_key($haystack, @array_flip($difference));
    }

    /**
     * Get the difference of 2 recursive collections based on associate pairs.
     *
     * @param array $haystack
     * @param array $difference
     * @return array
     */
    public static function diffAssoc(array $haystack, array $difference): array
    {
        if (static::isRecursive($difference)) {
            foreach ($haystack as $key => $element) {

                if (array_key_exists($key, $difference) && is_array($element)) {
                    $r_diff = static::diffAssoc($element, $difference[$key]);
                    !$r_diff ?: $o_diff[$key] = $r_diff;
                }

                if (!isset($o_diff[$key])
                    && (!in_array($element, $difference, true))
                        || !array_key_exists($key, $difference)) {

                    $o_diff[$key] = $element;
                }
            }

            return $o_diff ?? [];
        }

        return array_diff_assoc($haystack, $difference);
    }

    /**
     * Get the intersection of 2 recursive collections.
     *
     * @param array $haystack
     * @param array $difference
     * @return array
     */
    public static function intersect(array $haystack, array $difference): array
    {
        if (static::isRecursive($difference)) {
            foreach ($haystack as $key => $element) {

                if (array_key_exists($key, $difference) && is_array($element)) {
                    $r_diff = static::intersect($element, $difference[$key]);
                    !$r_diff ?: $o_diff[$key] = $r_diff;
                }
                else if (in_array($element, $difference, true)) {
                    $o_diff[$key] = $element;
                }
            }

            return $o_diff ?? [];
        }

        return array_intersect($haystack, $difference);
    }

    /**
     * Get the intersection of 2 recursive collections based on keys.
     *
     * @param array $haystack
     * @param array $difference
     * @return array
     */
    public static function intersectKey(array $haystack, array $difference): array
    {
        if (static::isRecursive($difference)) {
            foreach ($haystack as $key => $element) {

                if (array_key_exists($key, $difference) && is_array($element)) {
                    $r_diff = static::intersectKey($element, $difference[$key]);
                    !$r_diff ?: $o_diff[$key] = $r_diff;
                }
                else if (in_array($key, $difference, true)) {
                    $o_diff[$key] = $element;
                }
            }

            return $o_diff ?? [];
        }

        return array_intersect_key($haystack, @array_flip($difference));
    }

    /**
     * Get the difference of 2 recursive collections based on associate pairs.
     *
     * @param array $haystack
     * @param array $difference
     * @return array
     */
    public static function intersectAssoc(array $haystack, array $difference): array
    {
        if (static::isRecursive($difference)) {
            foreach ($haystack as $key => $element) {

                if (array_key_exists($key, $difference) && is_array($element)) {
                    $r_diff = static::intersectAssoc($element, $difference[$key]);
                    !$r_diff ?: $o_diff[$key] = $r_diff;
                }

                if (!isset($o_diff[$key])
                    && (in_array($element, $difference, true))
                    && array_key_exists($key, $difference)) {

                    $o_diff[$key] = $element;
                }
            }

            return $o_diff ?? [];
        }

        return array_intersect_assoc($haystack, $difference);
    }

    /**
     * Implode a recursive array.
     *
     * @param array $haystack
     * @param string $separator
     * @param Closure|null $callback
     * @return string
     */
    public static function implode(array $haystack, string $separator, Closure $callback = null): string
    {
        $filtered = static::filter($haystack, function($v) {
            return is_string($v);
        });

        foreach ($filtered as $key => &$value) {

            if(is_array($value)) {
                $value = self::implode($value, $separator, $callback);
            }
            else if($callback instanceof Closure) {
                $value = $callback($value, $key);
            }
        }

        return implode($separator, $filtered);
    }

    /**
     * Collapse a multidimensional array keeping the keys.
     *
     * @param array $haystack
     * @param Closure|null $callback
     * @param bool $overwrite
     * @return array
     */
    public static function collapse(array $haystack, Closure $callback = null, bool $overwrite = false): array
    {
        if(empty($haystack)) {
            return [];
        }

        array_walk_recursive($haystack,
            function ($v, $k) use (&$return, $overwrite) {
                $overwrite
                    ? $return[$k] = $v
                    : (isset($return[$k]) ? $return[] = $v : $return[$k] = $v);
            });

        return $callback instanceof Closure ? $callback($return) : $return;
    }

    /**
     * Flatten a multidimensional array without the keys.
     *
     * @param array $haystack
     * @param Closure|null $callback
     * @return array
     */
    public static function flatten(array $haystack, Closure $callback = null): array
    {
        array_walk_recursive($haystack,
            function ($v) use (&$return) {
                $return[] = $v;
            });

        return $callback instanceof Closure
            ? $callback($return)
            : $return;
    }

    /**
     * Collect values out of an array based on a key, keeping the parent key as the key.
     *
     * @param array $haystack
     * @param string|int $column
     * @param string|null $override
     * @return array
     */
    public static function gather(array $haystack, string|int $column, string $override = null): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($haystack), RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($iterator as $key => $value){

            if($key === $column) {
                $parent = $iterator->getSubIterator(($iterator->getDepth()-1))->key();
                $current = $iterator->getSubIterator(($iterator->getDepth()));

                ! is_null($override) && isset($current[$override])
                    ? $values[$current[$override]] = $value
                    : $values[$parent] = $value;
            }
        }

        return static::collapse($values ?? []);
    }

    /**
     * Separate items based on separation characters in a simple array.
     *
     * @param array $haystack
     * @param string $separators
     * @return array
     */
    public static function separate(array $haystack, string $separators): array
    {
        foreach ($haystack as $string) {
            $token = strtok($string, $separators);

            while (false !== $token) {
                $parts[] = trim($token);
                $token = strtok($separators);
            }
        }

        return $parts ?? [];
    }

    /**
     * Pair items in a recursive array split by a separator.
     *
     * @param array $haystack
     * @param string|array $separators
     * @return array
     */
    public static function pair(array $haystack, string|array $separators): array
    {
        foreach ($haystack as $string) {

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
     * Check if the array is recursive.
     *
     * @param array $haystack
     * @return bool
     */
    public static function isRecursive(array $haystack): bool
    {
        foreach ($haystack as $parameter) {
            if(is_array($parameter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we should handle the array as a recursive array.
     *
     * @param array $haystack
     * @param string|int $key
     * @return bool
     */
    protected static function shouldHandleRecursive(array $haystack, string|int $key): bool
    {
        return is_string($key) && (strpbrk($key, '.') !== false)
            && (empty($haystack) || static::isRecursive($haystack));
    }
}
