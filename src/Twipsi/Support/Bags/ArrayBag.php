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
use Countable;
use IteratorAggregate;

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
     * Return array of parameters if called.
     *
     * @return array
     */
    public function __invoke(): array
    {
        return $this->parameters;
    }

    /**
     * Return all the parameters excluding exceptions.
     *
     * @param string ...$exceptions
     *
     * @return array
     */
    public function all(string ...$exceptions): array
    {
        if (!func_get_args()) {
            return $this->parameters;
        }

        return array_filter(
            $this->parameters,
            function ($k) use ($exceptions) {
                return !in_array($k, $exceptions);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Return all the parameters with the selection key in one array
     *
     * @param string ...$keys
     *
     * @return array
     */
    public function selected(string ...$keys): array
    {
        if (!func_get_args()) {
            return $this->parameters;
        }

        return array_filter(
            $this->parameters,
            function ($k) use ($keys) {
                return in_array($k, $keys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Filter using a callback and replace the haystack.
     *
     * @param \Closure $callback
     * @return void
     */
    public function filter(\Closure $callback): void
    {
        $this->replace(
            array_filter($this->parameters, $callback)
        );
    }

    /**
     * Replace the current parameters entirely.
     *
     * @param array|ArrayBag $parameters
     *
     * @return static
     */
    public function replace(array|ArrayBag $parameters): static
    {
        if ($parameters instanceof ArrayBag) {
            $parameters = $parameters->all();
        }

        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Merge An array of paramters with current parameters.
     * 
     * @param array|ArrayBag $parameters
     * 
     * @return static
     */
    public function merge(array|ArrayBag $parameters): static
    {
        if ($parameters instanceof ArrayBag) {
            $parameters = $parameters->all();
        }

        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * Return all keys in the parameters.
     * 
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Set a parameter into parameters array.
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Add a parameter into parameters array.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function add(mixed $value): static
    {
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * Push a value into parameter with a key.
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function push(string $key, mixed $value): static
    {
        $this->parameters[$key][] = $value;

        return $this;
    }

    /**
     * Collect values out of an array based on a key
     * keeping the parent key as the key.
     * 
     * ex. ['container' => ['value' => 85552]].
     * output. ['container' => 85552].
     * 
     * @param string $pointer
     * 
     * @return array
     */
    public function gather(string $pointer): array
    {
        return array_map(function ($parent) use ($pointer) {

            return $parent[$pointer] ?? null;
        }, $this->parameters);
    }

    /**
     * Retrieve a parameter from parameters array.
     * 
     * @param string $key
     * @param mixed|null $default
     * 
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->parameters[$key] : $default;
    }

    /**
     * Return and remove an element based on a key.
     * 
     * @param string $key
     * @param mixed|null $default
     * 
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->has($key) ? $this->get($key) : $default;
        $this->delete($key);

        return $value;
    }

    /**
     * Check if parameter exists in parameters array.
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Search for a value and return the key.
     *
     * @param string $value
     * @return mixed
     */
    public function search(string $value): mixed
    {
        return array_search($value, $this->parameters);
    }

    /**
     * Check if value exists in parameters array.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public function contains(string $value): bool
    {
        return in_array($value, $this->parameters);
    }

    /**
     * Remove a parameter from parameters array.
     * 
     * @param string $key
     * 
     * @return static
     */
    public function delete(string $key): static
    {
        unset($this->parameters[$key]);

        return $this;
    }

    /**
     * Return and shift first element.
     * 
     * @return mixed
     */
    public function shift(): mixed
    {
        return array_shift($this->parameters);
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
     * Returns an iterator for parameters.
     * 
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }

    /**
     * Returns the number of parameters in the array.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->parameters);
    }

    /**
     * Returns the first parameter.
     * 
     * @return mixed
     */
    public function first(): mixed
    {
        return array_values($this->parameters)[0];
    }

    /**
     * Returns the last parameter.
     * 
     * @return mixed
     */
    public function last(): mixed
    {
        return array_reverse(array_values($this->parameters))[0];
    }
}
