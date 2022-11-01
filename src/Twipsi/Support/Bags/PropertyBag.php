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

use ReflectionObject;
use ReflectionProperty;

class PropertyBag
{
    /**
     * Construct our property storage.
     *
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        // Set the properties from an array.
        foreach ($properties as $property => $value) {
            if (is_string($property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
    * Return all the properties excluding exceptions.
    *
    * @param string ...$exceptions
    * @return array
    */
    public function all(string ...$exceptions): array
    {
        $public = (new ReflectionObject($this))->getProperties(
            ReflectionProperty::IS_PUBLIC
        );

        foreach ($public as $property) {
            if (!in_array($property->getName(), $exceptions)) {
                $properties[$property->getName()] = $property->getValue($this);
            }
        }

        return $properties ?? [];
    }

    /**
     * Return all the properties with the selection key in one array
     *
     * @param string ...$keys
     * @return array
     */
    public function selected(string ...$keys): array
    {
        if (!func_get_args()) {
            return $this->all();
        }

        return array_filter($this->all(),
            function ($k) use ($keys) {
                return in_array($k, $keys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Replace current properties with another.
     *
     * @param array $properties
     * @return $this
     */
    public function replace(array $properties): static
    {
        foreach (array_keys($this->all()) as $key) {
            unset($this->{$key});
        }

        $this->merge($properties);

        return $this;
    }

    /**
     * Return all property names in the class.
     *
     * @return array
     */
    public function keys(): array
    {
        $public = (new ReflectionObject($this))->getProperties(
            ReflectionProperty::IS_PUBLIC
        );

        foreach ($public as $property) {
            $keys[] = $property->getName();
        }

        return $keys ?? [];
    }

    /**
     * Set a parameter into class property.
     *
     * @param string|int $key
     * @param mixed $value
     * @return static
     */
    public function set(string|int $key, mixed $value): static
    {
        $this->{$key} = $value;

        return $this;
    }

    /**
     * Set multiple properties and values.
     * 
     * @param array $data
     * @return static
     */
    public function merge(array $data): static
    {
        foreach($data as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Get a property.
     *
     * @param string $property
     * @return mixed
     */
    public function get(string $property): mixed
    {
        return !isset($this->{$property}) ? null : $this->{$property};
    }

    /**
     * Check if the class has a property.
     *
     * @param string $property
     *
     * @return bool
     */
    public function has(string $property): bool
    {
        return isset($this->{$property});
    }

    /**
     * Check if the bag is empty.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->all());
    }

    /**
     * Remove a property from the class.
     * 
     * @param string $property
     * @return static
     */
    public function delete(string $property): static
    {
        unset($this->{$property});

        return $this;
    }
}
