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

namespace Twipsi\Support\Bags;

use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Twipsi\Support\Arr;

class ArrayBag implements IteratorAggregate, Countable
{
    /**
     * Contains parameters data.
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * Construct our array based parameters.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Collect data and create a new collection.
     *
     * @param array ...$parameters
     * @return self
     */
    public static function collect(array $parameters): self
    {
        return new self($parameters);
    }

    /**
     * Collect data and create a new collection convert it to an array
     *
     * @param mixed $parameters
     * @return self
     */
    public static function wrap(mixed $parameters): self
    {
        if($parameters instanceof static) {
            return new self($parameters->all());
        }

        if(! is_array($parameters)) {
            return new self([$parameters]);
        }

        return new self($parameters);
    }

    /**
     * Replace the current parameters entirely.
     *
     * @param array|static $parameters
     * @return $this
     */
    public function override(array|self $parameters): static
    {
        $this->parameters = $parameters instanceof static
            ? $parameters->all()
            : $parameters;

        return $this;
    }

    /**
     * Create a new instance.
     *
     * @param array $parameters
     * @return static
     */
    public function new(array $parameters = []): static
    {
        return $this->clone()->override($parameters);
    }

    /**
     * Clone the current instance with a callback.
     *
     * @param Closure|null $callback
     * @return static
     */
    public function clone(Closure $callback = null): static
    {
        if($callback instanceof Closure) {
            $bag = $callback(clone($this));
        }

        return $bag ?? clone($this);
    }

    /**
     * Enable tapping into collection without modifying the collection.
     *
     * @param Closure $callback
     * @return $this
     */
    public function tap(Closure $callback): static
    {
        $callback($this->parameters);

        return $this;
    }

    /**
     * Replace the current parameters in a new instance.
     *
     * @param array|static $parameters
     * @return static
     */
    public function replace(array|self $parameters): static
    {
        $parameters = $parameters instanceof static
            ? $parameters->all()
            : $parameters;

        return $this->new($parameters);
    }

    /**
     * Merge an array of parameters with current parameters in a new instance.
     *
     * @param array|static $parameters
     * @return static
     */
    public function merge(array|self $parameters): static
    {
        if ($parameters instanceof self) {
            $parameters = $parameters->all();
        }

        $parameters = $this->isRecursive()
            ? array_merge_recursive($this->parameters, $parameters)
            : array_merge($this->parameters, $parameters);

        return $this->new($parameters);
    }

    /**
     * Merge an array of parameters with current parameters.
     *
     * @param array|static $parameters
     * @return void
     */
    public function inject(array|self $parameters): void
    {
        $this->override($this->merge($parameters));
    }

    /**
     * Set a parameter into parameters array.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $recursive
     * @return $this
     */
    public function set(string $key, mixed $value, bool $recursive = true): static
    {
        $this->parameters = Arr::set($this->parameters, $key, $value, $recursive);

        return $this;
    }

    /**
     * Set a parameter into parameters array.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $recursive
     * @return $this
     */
    public function put(string $key, mixed $value, bool $recursive = true): static
    {
        return $this->set($key, $value, $recursive);
    }

    /**
     * Add a parameter into parameters array without setting a key.
     *
     * @param mixed $value
     * @return $this
     */
    public function add(mixed $value): static
    {
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * Prepend parameter to the front of the array.
     *
     * @param mixed $value
     * @param string|null $key
     * @param bool $recursive
     * @return $this
     */
    public function prepend(mixed $value, string $key = null, bool $recursive = true): static
    {
        $this->parameters = Arr::prepend($this->parameters, $value, $key, $recursive);

        return $this;
    }

    /**
     * Push a value into parameter with a specific key.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $recursive
     * @return static
     */
    public function push(string $key, mixed $value, bool $recursive = true): static
    {
        $this->parameters = Arr::push($this->parameters, $key, $value, $recursive);

        return $this;
    }

    /**
     * Check if all parameters exist in parameters array.
     *
     * @param string ...$keys
     * @return bool
     */
    public function has(string ...$keys): bool
    {
        return Arr::has($this->parameters, ...$keys);
    }

    /**
     * Check if any parameter exists in parameters array.
     *
     * @param string ...$keys
     * @return bool
     */
    public function hasAny(string ...$keys): bool
    {
        return Arr::hasAny($this->parameters, ...$keys);
    }

    /**
     * Find all specific endpoint keys in a recursive array.
     *
     * @param string ...$keys
     * @return mixed
     */
    public function find(string ...$keys): mixed
    {
        return Arr::find($this->parameters, ...$keys);
    }

    /**
     * Find any one specific endpoint key in a recursive array.
     *
     * @param string ...$keys
     * @return bool
     */
    public function findAny(string ...$keys): bool
    {
        return Arr::findAny($this->parameters, ...$keys);
    }

    /**
     * Remove a parameter from original parameters array.
     *
     * @param string ...$keys
     * @return $this
     */
    public function delete(string ...$keys): static
    {
        $this->parameters = Arr::delete($this->parameters, ...$keys);

        return $this;
    }

    /**
     * Remove certain keys from the collection using a closure.
     *
     * @param Closure $callback
     * @return static
     */
    public function reject(Closure $callback): static
    {
        return $this->replace(
            Arr::reject($this->parameters, $callback)
        );
    }

    /**
     * Remove a parameter from parameters array in a new instance.
     *
     * @param string ...$keys
     * @return static
     */
    public function forget(string ...$keys): static
    {
        return $this->clone()->delete(...$keys);
    }

    /**
     * Return all the parameters except...
     *
     * @param string ...$keys
     * @return array
     */
    public function except(string ...$keys): array
    {
        return $this->all(...$keys);
    }

    /**
     * Return all the parameters excluding exceptions.
     *
     * @param string ...$exceptions
     * @return array
     */
    public function all(string ...$exceptions): array
    {
        if (!func_get_args()) {
            return $this->parameters;
        }

        return Arr::delete($this->parameters, ...$exceptions);
    }

    /**
     * Return all the parameters with the selection key in one array
     *
     * @param string ...$keys
     * @return static
     */
    public function selected(string ...$keys): static
    {
        return $this->replace(
            Arr::only($this->parameters, ...$keys)
        );
    }

    /**
     * Return all the parameters with the selection key in one array
     *
     * @param string ...$keys
     * @return static
     */
    public function only(string ...$keys): static
    {
        return $this->selected(...$keys);
    }

    /**
     * Retrieve a parameter from parameters array.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->parameters, $key, $default);
    }

    /**
     * Return and remove an element based on a key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        is_null($value = $this->get($key)) ?: $this->delete($key);

        return $value ?? $default;
    }

    /**
     * Pop the last element in the collection.
     *
     * @param string|null $key
     * @return mixed
     */
    public function pop(string $key = null): mixed
    {
        if (!is_null($key)) {

            $parent = &$this->parameters;
            foreach(explode('.', $key) as $section) {
                $parent = &$parent[$section];
            }

            if(is_null($parent)) {
                throw new InvalidArgumentException(
                    sprintf("key [%s] could not be found in the haystack", $key)
                );
            }

            return array_pop($parent);
        }

        return array_pop($this->parameters);
    }

    /**
     * Return all values in the parameters in a new collection.
     *
     * @param bool $flatten
     * @return static
     */
    public function values(bool $flatten = false): static
    {
        return $this->replace(
            Arr::values($this->parameters, $flatten)
        );
    }

    /**
     * Return all keys in the parameters.
     *
     * @param bool $flatten
     * @return static
     */
    public function keys(bool $flatten = false): static
    {
        return $this->replace(
            Arr::keys($this->parameters, $flatten)
        );
    }

    /**
     * Check if there is only a single entity.
     *
     * @return bool
     */
    public function lonely(): bool
    {
        return $this->count() === 1;
    }

    /**
     * Check if the container is empty.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return !$this->parameters;
    }

    /**
     * Returns the number of parameters in the array.
     *
     * @param string|null $key
     * @return int
     */
    public function count(string $key = null): int
    {
        return Arr::count($this->parameters, $key);
    }

    /**
     * Get the sum of the array.
     *
     * @param string|null $key
     * @return int
     */
    public function sum(string $key = null): int
    {
        return Arr::sum($this->parameters, $key);
    }

    /**
     * Get the average value in an array.
     *
     * @param string|null $key
     * @return float|int
     */
    public function avg(string $key = null): float|int
    {
        return Arr::avg($this->parameters, $key);
    }

    /**
     * Get the min value in an array.
     *
     * @param string|null $key
     * @return mixed
     */
    public function min(string $key = null): mixed
    {
        return Arr::min($this->parameters, $key);
    }

    /**
     * Get the max value in an array.
     *
     * @param string|null $key
     * @return mixed
     */
    public function max(string $key = null): mixed
    {
        return Arr::max($this->parameters, $key);
    }

    /**
     * Returns the first parameter.
     *
     * @param Closure|null $callback
     * @param bool $flatten
     * @return mixed
     */
    public function first(Closure $callback = null, bool $flatten = false): mixed
    {
        return Arr::first($this->parameters, $callback, $flatten);
    }

    /**
     * Get the first where key is key and value is value.
     *
     * @param string $key
     * @param string|null $operator
     * @param mixed $value
     * @return mixed
     */
    public function firstWhere(string $key, string $operator = null, mixed $value = null): mixed
    {
        return Arr::firstWhere($this->parameters, $key, $operator, $value);
    }

    /**
     * Returns the last parameter.
     *
     * @param Closure|null $callback
     * @param bool $flatten
     * @return mixed
     */
    public function last(Closure $callback = null, bool $flatten = false): mixed
    {
        return Arr::last($this->parameters, $callback, $flatten);
    }

    /**
     * Get the last where key is key and value is value.
     *
     * @param string $key
     * @param string|null $operator
     * @param mixed $value
     * @return mixed
     */
    public function lastWhere(string $key, string $operator = null, mixed $value = null): mixed
    {
        return Arr::lastWhere($this->parameters, $key, $operator, $value);
    }

    /**
     * Search for a value and return the key.
     *
     * @param mixed $value
     * @param bool $strict
     * @return mixed
     */
    public function search(mixed $value, bool $strict = false): mixed
    {
        return Arr::search($this->parameters, $value, $strict);
    }

    /**
     * Find all specific values in a recursive array.
     *
     * @param mixed ...$values
     * @return bool
     */
    public function exists(mixed ...$values): bool
    {
        return Arr::exists($this->parameters, ...$values);
    }

    /**
     * Find any specific values in a recursive array.
     *
     * @param mixed ...$values
     * @return bool
     */
    public function existsAny(mixed ...$values): bool
    {
        return Arr::existsAny($this->parameters, ...$values);
    }

    /**
     * Get all the duplicate entities.
     *
     * @param bool $strict
     * @return array
     */
    public function duplicates(bool $strict = false): array
    {
        $normalized = ! $strict
            ? array_map('strtolower', $this->parameters)
            : $this->parameters;

        return array_unique(
            Arr::diffAssoc($normalized, Arr::unique($normalized))
        );
    }

    /**
     * Check if value exists in parameters array.
     *
     * @param mixed $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        if($value instanceof Closure) {
            return !empty(Arr::filter($this->parameters, $value, true));
        }

        if(is_array($value)) {
            return $this->has($key = array_key_first($value))
                && $this->get($key) === reset($value);
        }

        return false !== $this->search($value, true);
    }

    /**
     * Check if value doesnt exist in parameters array.
     *
     * @param mixed $value
     * @return bool
     */
    public function missing(mixed $value): bool
    {
        return !$this->contains($value);
    }

    /**
     * Return unique results in a new instance.
     *
     * @param array|null $merge
     * @return static
     */
    public function unique(array $merge = null): static
    {
        return $this->replace(
            Arr::unique($this->parameters, $merge)
        );
    }

    /**
     * Flip key/value pairs.
     *
     * @return static
     */
    public function flip(): static
    {
        return $this->replace(
            @array_flip($this->parameters)
        );
    }

    /**
     * Reverse the order of the collection.
     *
     * @return static
     */
    public function reverse(): static
    {
        return $this->replace(
            array_reverse($this->parameters)
        );
    }

    /**
     * Sort the collection by specifying the sort function.
     *
     * @param string $function
     * @param Closure|null $callback
     * @param int $flags
     * @return static
     */
    public function sort(string $function = 'sort', Closure|null $callback = null, int $flags = SORT_REGULAR): static
    {
        $clone = $this->parameters;

        $callback instanceof Closure
            ? $function($clone, $callback)
            : $function($clone, $flags);

        return $this->replace($clone);
    }

    /**
     * Sort the collection by a key name.
     *
     * @param Closure|string $key
     * @return static
     */
    public function sortBy(Closure|string $key): static
    {
        $clone = $this->parameters;

        if($key instanceof Closure) {
            uasort($clone, $key);
        } else {
            uasort($clone, function($a, $b) use($key) {
                return $a[$key] <=> $b[$key];
            });
        }

        return $this->replace($clone);
    }

    /**
     * Return and shift n amount of elements in the original collection.
     *
     * @param int $amount
     * @return mixed
     */
    public function shift(int $amount = 1): mixed
    {
        if($amount === 1) {
            return array_shift($this->parameters);
        }

        for($i = $amount; $i > 0; $i--) {
            $results[] = array_shift($this->parameters);
        }

        return $results ?? null;
    }

    /**
     * Return and shift n amount of elements in a new instance.
     *
     * @param Closure|int $amount
     * @return static
     */
    public function skip(Closure|int $amount): static
    {
        $clone = $this->clone();

        if($amount instanceof Closure) {
            return $this->replace(Arr::filter($clone->all(), $amount));
        }

        $clone->shift($amount);

        return $clone;
    }

    /**
     * Slice the collection at an offset and return it in a new instance.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset = 1, int $length = null): static
    {
        return $this->replace(
            array_slice($this->parameters, $offset, $length)
        );
    }

    /**
     * Splice the collection at an offset and return result in a new instance.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function splice(int $offset = 1, int $length = null): static
    {
        return $this->replace(
            array_splice($this->parameters, $offset, $length)
        );
    }

    /**
     * Split the collection in n parts.
     *
     * @param int $in
     * @return static
     */
    public function split(int $in): static
    {
        return $this->chunk((int)ceil($this->count() / $in));
    }

    /**
     * Chunk an array into multiple parts.
     *
     * @param int $by
     * @return  static
     */
    public function chunk(int $by):  static
    {
        $clone = $this->parameters;

        while(!empty($clone)) {
            $parts[] = array_splice($clone, 0, $by);
        }

        return $this->replace($parts ?? []);
    }

    /**
     * Set the chunk by page number.
     *
     * @param int $page
     * @param int $chunk
     * @return  static
     */
    public function page(int $page, int $chunk):  static
    {
        $clone = $this->parameters;

        while(!empty($clone)) {
            $parts[] = array_splice($clone, 0, $chunk);
        }

        $page = max(($page-1), 0);

        return $this->replace($parts[$page] ?? []);
    }

    /**
     * Loop through the parameters passing them to a closure.
     *
     * @param Closure $callback
     * @return static
     */
    public function loop(Closure $callback):  static
    {
        return $this->new(
            Arr::loop($this->parameters, $callback)
        );
    }

    /**
     * Check if every element passes the callback logics.
     *
     * @param Closure $callback
     * @return bool
     */
    public function every(Closure $callback): bool
    {
        return Arr::every($this->parameters, $callback);
    }

    /**
     * Loop through an array with a closure until we receive a return.
     *
     * @param Closure $callback
     * @return mixed
     */
    public function attempt(Closure $callback): mixed
    {
        return Arr::attempt($this->parameters, $callback);
    }

    /**
     * Map using a callback and replace the original collection.
     *
     * @param Closure $callback
     * @return void
     */
    public function transform(Closure $callback): void
    {
        $this->parameters = $this->map($callback)
            ->all();
    }

    /**
     * Map using a callback and replace the haystack in a new instance.
     *
     * @param Closure $callback
     * @return static
     */
    public function map(Closure $callback): static
    {
        return $this->replace(
            Arr::map($this->parameters, $callback)
        );
    }

    /**
     * Map using a callback and replacing key/value pair.
     *
     * @param Closure $callback
     * @return static
     */
    public function mapPair(Closure $callback): static
    {
        return $this->replace(
            Arr::mapPair($this->parameters, $callback)
        );
    }

    /**
     * Filter using a callback and replace the haystack.
     *
     * @param Closure $callback
     * @return static
     */
    public function filter(Closure $callback): static
    {
        return $this->replace(
            Arr::filter($this->parameters, $callback, true, true)
        );
    }

    /**
     * Set the difference compared to an array by value.
     *
     * @param array $difference
     * @return static
     */
    public function diff(array $difference): static
    {
        return $this->replace(
            Arr::diff($this->parameters, $difference)
        );
    }

    /**
     * Set the difference compared to an array by key.
     *
     * @param array $difference
     * @return static
     */
    public function diffKey(array $difference): static
    {
        return $this->replace(
            Arr::diffKey($this->parameters, $difference)
        );
    }

    /**
     * Set the difference compared to an array by key/value pair.
     *
     * @param array $difference
     * @return static
     */
    public function diffAssoc(array $difference): static
    {
        return $this->replace(
            Arr::diffAssoc($this->parameters, $difference)
        );
    }

    /**
     * Set the intersection compared to an array by value.
     *
     * @param array $intersection
     * @return static
     */
    public function intersect(array $intersection): static
    {
        return $this->replace(
            Arr::intersect($this->parameters, $intersection)
        );
    }

    /**
     * Set the intersection compared to an array by key.
     *
     * @param array $intersection
     * @return static
     */
    public function intersectKey(array $intersection): static
    {
        return $this->replace(
            Arr::intersectKey($this->parameters, $intersection)
        );
    }

    /**
     * Set the intersection compared to an array assoc.
     *
     * @param array $intersection
     * @return static
     */
    public function intersectAssoc(array $intersection): static
    {
        return $this->replace(
            Arr::intersectAssoc($this->parameters, $intersection)
        );
    }

    /**
     * Process an array reduce on the collection.
     *
     * @param Closure $callback
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(Closure $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->parameters, $callback, $initial);
    }

    /**
     * Implode the parameters as a string.
     *
     * @param string $separator
     * @param Closure|null $callback
     * @return string
     */
    public function implode(string $separator, Closure $callback = null): string
    {
        return Arr::implode($this->parameters, $separator, $callback);
    }

    /**
     * Implode the gathered columns.
     *
     * @param string $column
     * @param string $separator
     * @return string
     */
    public function implodeBy(string $column, string $separator): string
    {
        return implode($separator, $this->gather($column));
    }

    /**
     * Collapse a multidimensional array keeping the keys.
     *
     * @param Closure|null $callback
     * @param bool $overwrite
     * @return static
     */
    public function collapse(Closure $callback = null, bool $overwrite = false): static
    {
        return $this->replace(
            Arr::collapse($this->parameters, $callback, $overwrite)
        );
    }

    /**
     * Flatten a multidimensional array without the keys.
     *
     * @param Closure|null $callback
     * @return static
     */
    public function flatten(Closure $callback = null): static
    {
        return $this->replace(
            Arr::flatten($this->parameters, $callback)
        );
    }

    /**
     * Collect values out of an array based on a key
     * keeping the parent key as the key.
     *
     * @param string $key
     * @param string|null $override
     * @return array
     */
    public function gather(string $key, string $override = null): array
    {
        return Arr::gather($this->parameters, $key, $override);
    }

    /**
     * Separate items based on separation characters in a simple array.
     *
     * @param string $separators
     * @return static
     */
    public function separate(string $separators): static
    {
        return $this->replace(
            Arr::separate($this->parameters, $separators)
        );
    }

    /**
     * Pair items in a recursive array split by a separator.
     *
     * @param string|array $separators
     * @return static
     */
    public function pair(string|array $separators): static
    {
        return $this->replace(
            Arr::pair($this->parameters, $separators)
        );
    }

    /**
     * Dump the collection and exit.
     *
     * @return void
     */
    public function debug(): void
    {
        $this->dump();
        exit(1);
    }

    /**
     * Dump the collection.
     *
     * @return void
     */
    public function dump(): void
    {
        var_dump($this->parameters);
    }

    /**
     * Check if the array is recursive.
     *
     * @return bool
     */
    public function isRecursive(): bool
    {
        foreach ($this->parameters as $parameter) {
            if(is_array($parameter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an iterator for parameters.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }

    /**
     * Return array of parameters if called.
     *
     * @return array
     */
    public function __invoke(): array
    {
        return $this->parameters;
    }
}
