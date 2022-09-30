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

namespace Twipsi\Components\Model;

use Exception;
use Throwable;
use Twipsi\Components\Model\Exceptions\ModelPropertyException;
use Twipsi\Components\Model\Interfaces\ModelInterface;
use Twipsi\Facades\App;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;

class Model extends BaseModel implements ModelInterface
{
    use HandlesQueryBuilder, HandlesRelations;

    /**
     * The application object.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Custom filters to use when queried.
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * Model Constructor.
     */
    final public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->app = App::getInstance();
    }

    /**
     * Fetch all the models in the table.
     *
     * @param string ...$columns
     * @return ModelCollection
     * @throws ApplicationManagerException
     * @throws NotSupportedException
     */
    public static function fetchAll(string ...$columns): ModelCollection
    {
        return static::query()->get(...$columns);
    }

    /**
     * Update the model data to db.
     *
     * @param array $properties
     * @return bool
     * @throws ModelPropertyException
     * @throws Exception
     */
    public function update(array $properties = []): bool
    {
        return $this->live && $this->fill($properties)->save();
    }

    /**
     * Update db data wrapped in transaction.
     *
     * @param array $properties
     * @return bool
     * @throws ApplicationManagerException
     * @throws ModelPropertyException
     * @throws Throwable
     */
    public function updateTransact(array $properties = []): bool
    {
        return $this->live && $this->fill($properties)->saveTransact();
    }

    /**
     * Save the model back to the database.
     *
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        $factory = $this->createModelQuery();

        $saved = $this->live
                ? (!$this->isNotInitial() || $this->handleUpdate($factory))
                : $this->handleInsert($factory);

        !$saved ?: $this->handleAfterSave();

        return $saved;
    }

    /**
     * Save the model wrapped in transaction.
     *
     * @return bool
     * @throws ApplicationManagerException
     * @throws Throwable
     */
    public function saveTransact(): bool
    {
        return $this->getConnection()
            ->getDispatcher()
            ->transaction(function () {
                return $this->save();
        });
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     * @throws Exception
     */
    public function destroy(): bool
    {
        $factory = $this->createModelQuery();

        return $this->live && $this->handleDelete($factory);
    }

    /**
     *  Handle delete wrapped in a transaction.
     *
     * @return bool
     * @throws ApplicationManagerException
     * @throws Throwable
     */
    public function deleteTransact(): bool
    {
        return $this->getConnection()
            ->getDispatcher()
            ->transaction(function () {
                return $this->destroy();
            });
    }

    /**
     * Finalize saving method hooks.
     *
     * @return void
     */
    public function handleAfterSave(): void
    {
        // Put the current attributes as the original ones.
        $this->syncOriginal();
    }

    /**
     * Handle Updating model in the database.
     *
     * @param ModelQueryFactory $factory
     * @return bool
     * @throws Exception
     */
    public function handleUpdate(ModelQueryFactory $factory): bool
    {
        // Update timestamps
        !$this->usesTimeStamps() ?: $this->updateTimeStamps();

        if(!empty($modified = $this->getModified())) {
            $factory->where($this->primary(), '=', $this->savingKey())
                ->update($modified);

            $this->syncChanges();
        }

        return true;
    }

    /**
     * Handle inserting the model to the database.
     *
     * @param ModelQueryFactory $factory
     * @return bool
     */
    public function handleInsert(ModelQueryFactory $factory): bool
    {
        // Update timestamps
        !$this->usesTimeStamps() ?: $this->updateTimeStamps();

        if(empty($properties = $this->all())) {
            return true;
        }

        if($this->isIncrementing()) {

            // We will let the database handle the id,
            // and set the last inserted id as the current.
            unset($properties[$this->primary()]);
            $factory->insert($properties);

            $this->set($this->primary(),
                $factory->getDispatcher()->lastInserted()
            );
        } else {
            $factory->insert($properties);
        }

        $this->live = true;
        return true;
    }

    /**
     * Handle deleting the model from the database.
     *
     * @param ModelQueryFactory $factory
     * @return bool
     * @throws Exception
     */
    public function handleDelete(ModelQueryFactory $factory): bool
    {
        $factory->where($this->primary(), '=', $this->savingKey())
                ->delete();

        $this->live = false;

        return true;
    }

    /**
     * Create a new existing instance from builder.
     *
     * @param array $properties
     * @return $this
     * @throws ModelPropertyException
     */
    public function newLiveInstance(array $properties = []): static
    {
        // Create a new empty live model instance
        $model = $this->newModelInstance([], true);

        return $model->setProperties($properties, true);
    }

    /**
     * Create a new model instance.
     *
     * @param array $properties
     * @param bool $lives
     * @return static
     * @throws ModelPropertyException
     */
    public function newModelInstance(array $properties = [], bool $lives = false): static
    {
        $model = new static;
        $model->lives($lives);

        $model->fill($properties);

        return $model;
    }

    /**
     * Forward calls into the model.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        if(!method_exists($this, $method)) {
            return $this->newQuery()->{$method}(...$parameters);
        }

        return $this->$method(...$parameters);
    }

    /**
     * Forward static calls into the model.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static)->$method(...$parameters);
    }
}
