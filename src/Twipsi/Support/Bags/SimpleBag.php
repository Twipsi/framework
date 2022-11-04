<?php

namespace Twipsi\Support\Bags;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class SimpleBag implements IteratorAggregate, Countable
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
     * @return array
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Set a parameter into parameters array.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Push a value into parameter with a key.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function push(string $key, mixed $value): static
    {
        $this->parameters[$key][] = $value;

        return $this;
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
        return $this->has($key) ? $this->parameters[$key] : $default;
    }

    /**
     * Check if parameter exists in parameters array.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Remove a parameter from parameters array.
     *
     * @param string $key
     * @return static
     */
    public function delete(string $key): static
    {
        unset($this->parameters[$key]);

        return $this;
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
}