<?php

namespace Twipsi\Components\Model;

use Twipsi\Support\Bags\ArrayBag;

class ModelCollection extends ArrayBag
{
    protected array $ids = [];

    public function __construct(array $models = [])
    {
        parent::__construct($models);

        // Set the properties from an array.
        foreach ($models as $model) {
            $this->ids[] = $model->key();
        }
    }

    public function modelIDs(): array
    {
        return $this->ids;
    }
}