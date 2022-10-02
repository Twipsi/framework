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
}