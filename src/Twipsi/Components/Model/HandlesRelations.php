<?php

namespace Twipsi\Components\Model;

trait HandlesRelations
{
    /**
     * The relations container.
     *
     * @var array
     */
    protected array $relations = [];

    /**
     * Eager loaded relations.
     *
     * @var array
     */
    protected array $with = [];

    /**
     * Initiate model with loaded relations.
     *
     * @param string ...$relations
     * @return ModelQueryFactory
     */
    public static function with(string ...$relations): ModelQueryFactory
    {
        return static::query()->with(...$relations);
    }

    /**
     * Eager load relations on the model.
     *
     * @param string ...$relations
     * @return $this
     */
    public function eagerLoad(string ...$relations): Model
    {
        $this->newNonRelatedQuery()
            ->with(...$relations)
            ->eager($this);

        return $this;
    }

    /**
     * Eager load relationships on the polymorphic relation of a model.
     * #Fixit
     * @param string $relation
     * @param string ...$relations
     * @return $this
     */
    public function eagerLoadMorph(string $relation, string ...$relations): Model
    {
        if (! $this->{$relation}) {
            return $this;
        }

        $className = get_class($this->{$relation});

        $this->{$relation}->eagerLoad($relations[$className] ?? []);

        return $this;
    }

    /**
     * Eager load relations on the model if they are not already eager loaded.
     * #Fixit
     * @param string ...$relations
     * @return $this
     */
    public function eagerLoadMissing(string ...$relations): Model
    {
        $this->newCollection($this)
            ->eagerLoadMissing($relations);

        return $this;
    }

    public function push()
    {
        if (! $this->save()) {
            return false;
        }

        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model via this "push" method, which allows
        // us to recurse into all of these nested relations for the model instance.
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection
                ? $models->all() : [$models];

            foreach (array_filter($models) as $model) {
                if (! $model->push()) {
                    return false;
                }
            }
        }

        return true;
    }
}