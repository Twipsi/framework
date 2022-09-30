<?php

namespace Twipsi\Components\Model;

use Twipsi\Support\Bags\PropertyBag;
use Twipsi\Components\Model\Exceptions\ModelPropertyException;

abstract class ModelContainer extends PropertyBag
{
    /**
     * The default values to use for properties.
     *
     * @var array
     */
    protected array $default = [];

    /**
     * All the properties that can be included in db query.
     *
     * @var array
     */
    protected array $fillables = [];

    /**
     * All the properties that should not be included in db query.
     *
     * @var array
     */
    protected array $guarded = ['*'];

    /**
     * All the properties that could not be filled.
     *
     * @var array
     */
    protected array $breached = [];

    /**
     * Weather we should throw exception if a non-fillable property is provided.
     *
     * @var bool
     */
    protected static bool $strict = true;

    /**
     * Weather we can bypass the guards to fill properties.
     *
     * @var bool
     */
    protected static bool $bypassable = false;

    /**
     * Fill in the model properties.
     *
     * @param array $properties
     * @return $this
     * @throws ModelPropertyException
     */
    public function fill(array $properties): self
    {
        $fillables = $this->parseFillables($properties);

        foreach ($fillables as $column => $value) {

            if($this->isFillable($column)) {
                $this->set($column, $value);

            } elseif ($this->isLocked()) {
                throw new ModelPropertyException (
                    "The model is totally locked. Please add your fillable properties to the model."
                );
            }
        }

        // Save all the properties that could not be filled, so we can log them later.
        if(count($fillables) !== count($properties)) {
            $this->breached[] = array_diff($properties, $fillables);

            if(static::isModelStrict()) {
                throw new ModelPropertyException(sprintf(
                    "The '%s' properties are not fillable", implode(', ', $this->breached)
                ));
            }
        }

        return $this;
    }

    /**
     * Forcefully fill in properties.
     *
     * @param array $properties
     * @return $this
     * @throws ModelPropertyException
     */
    public function forceFill(array $properties): self
    {
        static::bypass();
        $this->fill($properties);

        static::bypass(false);

        return $this;
    }

    /**
     * Set an array of properties to fillable.
     *
     * @param array $fillables
     * @return $this
     */
    public function fillable(array $fillables): self
    {
        $this->fillables = $fillables;

        return $this;
    }

    /**
     * Get all the fillable properties.
     *
     * @return array|string[]
     */
    public function fillables(): array
    {
        return $this->fillables;
    }

    /**
     * Check if a property is fillable.
     *
     * @param string $property
     * @return bool
     */
    public function isFillable(string $property): bool
    {
        if(in_array($property, $this->fillables())) {
            return true;
        }

        if($this->isGuarded($property)) {
            return false;
        }

        return empty($this->fillables())
            && ! str_contains($property, '.')
            && ! str_starts_with($property, '_');
    }

    /**
     * Guard an array of properties.
     *
     * @param array $guarded
     * @return $this
     */
    public function guard(array $guarded): self
    {
        $this->guarded = $guarded;

        return $this;
    }

    /**
     * Get all the guarded properties.
     *
     * @return array|string[]
     */
    public function guarded(): array
    {
        return $this->guarded;
    }

    /**
     * Set the bypassable mode.
     *
     * @param bool $enable
     * @return void
     */
    public static function bypass(bool $enable = true): void
    {
        static::$bypassable = $enable;
    }

    /**
     * Check if guard is bypassable.
     *
     * @return bool
     */
    public static function bypassable(): bool
    {
        return static::$bypassable;
    }

    /**
     * Check if a property is guarded.
     * 
     * @param string $property
     * @return bool
     */
    public function isGuarded(string $property): bool
    {
        return in_array($property, $this->guarded())
            || $this->guarded() == ['*'];
    }

    /**
     * Check if every possible property is locked from db query.
     * 
     * @return bool
     */
    public function isLocked(): bool
    {
        return empty($this->fillables())
            && $this->guarded() == ['*'];
    }

    /**
     * Get all the properties that could not be filled.
     *
     * @return array
     */
    public function breached(): array
    {
        return $this->breached();
    }

    /**
     * Parse the provided properties and intersect the fillables.
     *
     * @param array $properties
     * @return array
     */
    protected function parseFillables(array $properties): array
    {
        if(! empty($fillables = $this->fillables()) && !static::$bypassable) {

            return array_filter($properties,
                function ($k) use ($fillables) {
                    return !in_array($k, $fillables);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $properties;
    }

    /**
     * Check if we are in strict mode.
     *
     * @return bool
     */
    public function isModelStrict(): bool
    {
        return static::$strict;
    }

    /**
     * Set model to strict mode.
     *
     * @param bool $strict
     * @return void
     */
    public function throwExceptionOnBreach(bool $strict = true): void
    {
        static::$strict = $strict;
    }
}