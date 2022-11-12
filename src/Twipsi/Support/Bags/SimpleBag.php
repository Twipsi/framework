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
     * Inject data into the collection.
     *
     * @param array|SimpleBag|ArrayBag $collection
     * @return $this
     */
    public function inject(array|SimpleBag|ArrayBag $collection): static
    {
        if($collection instanceof SimpleBag) {
            $this->parameters += $collection->all();
        }
        else if($collection instanceof ArrayBag) {
            $this->parameters += $collection->collapse()->all();
        }
        else {
            $this->parameters += $collection;
        }

        return $this;
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
     * Add a key/value pair into a parent key.
     *
     * @param string $key
     * @param array $value
     * @return $this
     */
    public function add(string $key, array $value): static
    {
        $this->parameters[$key][array_key_first($value)] = reset($value);

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
     * Get a value of a key and remove it from the collection.
     *
     * @param string $key
     * @return mixed
     */
    public function pull(string $key): mixed
    {
        $result = $this->get($key);
        $this->delete($key);

        return $result;
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
     * Search for a value in the collection.
     *
     * @param string $value
     * @return string|int|null
     */
    public function search(string $value): null|string|int
    {
        return false !== ($key = array_search($value, $this->parameters)) ? $key : null;
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
     * Return and shift first element.
     *
     * @return mixed
     */
    public function shift(): mixed
    {
        return array_shift($this->parameters);
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