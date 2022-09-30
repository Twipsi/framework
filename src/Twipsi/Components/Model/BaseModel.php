<?php

namespace Twipsi\Components\Model;

use Twipsi\Support\Chronos;
use Twipsi\Support\Str;

abstract class BaseModel extends ModelContainer
{
    /**
     * If the model exits in the database.
     *
     * @var bool
     */
    protected bool $live = false;

    /**
     * The table the model refers to.
     *
     * @var string
     */
    protected string $table;

    /**
     * The tables primary key.
     *
     * @var string
     */
    protected string $primary = 'id';

    /**
     * The tables primary key type.
     *
     * @var string
     */
    protected string $primaryType = 'int';

    /**
     * Weather the primary key is incrementing.
     *
     * @var bool
     */
    protected bool $primaryIncrement = true;

    /**
     * Weather to use timestamps on queries.
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * The default format to use for timestamps.
     *
     * @var string
     */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /**
     * The name of the column storing created date.
     *
     * @var string
     */
    protected string $createdColumn = 'created_at';

    /**
     * The name of the column storing updated date.
     *
     * @var string
     */
    protected string $updatedColumn = 'updated_at';

    /**
     * The original properties container.
     *
     * @var array
     */
    protected array $original = [];

    /**
     * The changed properties container.
     *
     * @var array
     */
    protected array $changes = [];

    /**
     * Set the existence of the model in the database.
     *
     * @param bool $state
     * @return $this
     */
    public function lives(bool $state = true): self
    {
        $this->live = $state;

        return $this;
    }

    /**
     * Get the table name of the model.
     *
     * @return string
     */
    public function table(): string
    {
        // If we have it manually defined use it,
        // otherwise generate from class name.
        return $this->table
            ?? Str::hay(get_class($this))->snake();
    }

    /**
     * Get the primary key of the model.
     *
     * @return string
     */
    public function primary(): string
    {
        // If we have it manually defined use it,
        // otherwise default to 'id'.
        return $this->primary ?? 'id';
    }

    /**
     * Get the primary key of the model with the table prefix.
     *
     * @return string
     */
    public function tablePrimary(): string
    {
        return $this->setTableOnColumn($this->primary());
    }

    /**
     * Get the primary key value.
     *
     * @return string|int
     */
    public function key(): string|int
    {
        $value = $this->get($this->primary());

        $type = $this->primaryType ?? 'int';

        return match ($type) {
            'string' => (string)$value,
            default => (int)$value,
        };
    }

    /**
     * Check if we are incrementing primary key.
     *
     * @return bool
     */
    public function isIncrementing(): bool
    {
        return $this->primaryIncrement;
    }

    /**
     * Return the primary key for saving.
     *
     * @return mixed
     */
    protected function savingKey(): mixed
    {
        return $this->original[$this->primary()] ?? $this->key();
    }

    /**
     * Get the default foreign key to use.
     *
     * @return string
     */
    public function foreign(): string
    {
        return $this->table().'_'.$this->primary();
    }

    /**
     * Prepend the table name on the column.
     *
     * @param string|array $columns
     * @return string|array
     */
    public function setTableOnColumn(string|array $columns): string|array
    {
        return is_array($columns)
            ? array_map([$this, 'setTableOnColumn'], $columns)
            : $this->table().'.'.$columns;
    }

    /**
     * Return the created at column name.
     *
     * @return string
     */
    public function createdAt(): string
    {
        return $this->createdColumn;
    }

    /**
     * Return the updated at column name.
     *
     * @return string
     */
    public function updatedAt(): string
    {
        return $this->updatedColumn;
    }

    /**
     * Sync current values to original.
     *
     * @return $this
     */
    public function syncOriginal(): self
    {
        $this->original = $this->all();

        return $this;
    }

    /**
     * Sync the changes made since fetch.
     *
     * @return $this
     */
    public function syncChanges(): self
    {
        $this->changes = $this->getModified();

        return $this;
    }

    /**
     * Reset all the changes made, since the last sync.
     *
     * @return $this
     */
    public function resetChanges(): self
    {
        $this->replace($this->getOriginalProperties());
        $this->changes = [];

        return $this;
    }

    /**
     * Check if any properties have been modified since the
     * last save sync done.
     *
     * @param string ...$properties
     * @return bool
     */
    public function isNotInitial(string ...$properties): bool
    {
        return $this->parseChanges(
            $this->getModified(), $properties
        );
    }

    /**
     * Check if any properties or the model is the same since
     * the last sync.
     *
     * @param string ...$properties
     * @return bool
     */
    public function isInitial(string ...$properties): bool
    {
        return ! $this->isNotInitial(...$properties);
    }

    /**
     * Check if any properties have been changed since first fetch.
     *
     * @param string ...$properties
     * @return bool
     */
    public function wasChanged(string ...$properties): bool
    {
        return $this->parseChanges(
            $this->getChangedProperties(), $properties
        );
    }

    /**
     * Get property changes since the last sync.
     *
     * @return array
     */
    public function getModified(): array
    {
        foreach($this->all() as $property => $value) {
            if(! $this->isOriginalProperty($property, $value)) {
                $modified[$property] = $value;
            }
        }

        return $modified ?? [];
    }

    /**
     * Get the changed property values.
     *
     * @return array
     */
    public function getChangedProperties(): array
    {
        return $this->changes;
    }

    /**
     * Get the original property values.
     *
     * @return array
     */
    public function getOriginalProperties(): array
    {
        return $this->original;
    }

    /**
     * Check if the value is the original one.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function isOriginalProperty(string $key, mixed $value): bool
    {
        if(! array_key_exists($key, $this->getOriginalProperties())) {
            return false;
        }

        return $this->getOriginalProperties()[$key] === $value;
    }

    /**
     * Check if any changes have been made to property list.
     *
     * @param array $changes
     * @param array $properties
     * @return bool
     */
    protected function parseChanges(array $changes, array $properties = []): bool
    {
        if(empty($properties)) {
            return count($changes) > 0;
        }

        foreach ($properties as $property){
            if (array_key_exists($property, $changes)) {
                 return true;
            }
        }

        return false;
    }

    /**
     * Set model properties without guard parsing.
     *
     * @param array $properties
     * @param bool $sync
     * @return $this
     */
    public function setProperties(array $properties, bool $sync = false): self
    {
        $this->replace($properties);

        if($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Generate the current time.
     *
     * @return string
     */
    public function generateTime(): string
    {
        return Chronos::date()
            ->setDateTimeFormat($this->dateFormat)
            ->getDateTime();
    }

    /**
     * Check if we are using timestamps.
     *
     * @return bool
     */
    public function usesTimeStamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * Update the timestamps before syncing.
     *
     * @return $this
     */
    public function updateTimeStamps(): self
    {
        if(!$this->isNotInitial($this->updatedColumn)) {
            $this->set($this->updatedColumn, $this->generateTime());
        }

        if( !$this->live && !$this->isNotInitial($this->createdColumn)) {
            $this->set($this->createdColumn, $this->generateTime());
        }

        return $this;
    }

    /**
     * Return the model name when called as string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return get_class($this);
    }

    /**
     * Dynamically get model property.
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Dynamically set model property.
     *
     * @param $key
     * @param $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
}