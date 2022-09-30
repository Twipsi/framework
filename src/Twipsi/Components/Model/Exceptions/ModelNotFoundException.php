<?php

namespace Twipsi\Components\Model\Exceptions;

use RuntimeException;
use Twipsi\Components\Model\Model;

class ModelNotFoundException extends RuntimeException
{
    /**
     * The model in hand.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * The ids we could not find.
     *
     * @var array
     */
    protected array $ids;

    /**
     * Construct a Model not found Exception.
     *
     * @param Model $model
     * @param array $ids
     */
    public function __construct(Model $model, array $ids = [])
    {
        $this->model = $model;
        $this->ids = $ids;

        count($ids) > 0
            ? $partial = ' '.implode(', ', $ids)
            : $partial = '.';

        $message = "No query results for model [{$model}] with IDS ({$partial})";

        parent::__construct($message, 404);
    }
}