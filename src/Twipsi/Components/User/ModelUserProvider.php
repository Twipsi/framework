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

namespace Twipsi\Components\User;

use InvalidArgumentException;
use Twipsi\Components\Model\Model;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Support\Hasher;
use Twipsi\Support\Str;

final class ModelUserProvider implements IUserProvider
{
    /**
     * The model we should use.
     *
     * @var string
     */
    protected string $model;

    /**
     * UserProvider Constructor.
     *
     * @param string $model
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by userID.
     *
     * @param int $id
     * @return Authable|null
     */
    public function getByID(int $id): ?Authable
    {
        return (($model = $this->buildModel($this->model)) instanceof Authable)
            ? $model
                ->query()
                ->where($model->getIDColumn(), "=", $id)
                ->first()
            : null;
    }

    /**
     * Retrieve a user by user token.
     *
     * @param int $id
     * @param string $token
     * @return Authable|null
     */
    public function getByToken(int $id, string $token): ?Authable
    {
        $model = $this->getByID($id);

        if ($model && ($remember = $model->getRememberToken())) {
            return Hasher::checkHash($remember, $token) ? $model : null;
        }

        return null;
    }

    /**
     * Retrieve a user by user credentials.
     *
     * @param array $data
     * @return Authable|null
     */
    public function getByCredentials(array $data): ?Authable
    {
        // Remove the password field because it won't match with the saved hash.
        $credentials = array_filter($data,
            function ($k) {
                return !Str::hay($k)->resembles("password");
            },
            ARRAY_FILTER_USE_KEY
        );

        if (is_null($query = $this->buildModel($this->model)->query())) {
            return null;
        }

        foreach ($credentials as $name => $credential) {
            $query->where($name, "=", $credential);
        }

        return $query->first() ?: null;
    }

    /**
     * Update the (remember me) user token.
     *
     * @param Authable&Model $user
     * @param string $token
     * @return void
     */
    public function updateUserToken(Authable&Model $user, string $token): void
    {
        $user->set($user->getTokenColumn(), $token)->save();
    }

    /**
     * Validate a user based on provided credentials.
     *
     * @param Authable $user
     * @param array $data
     * @return bool
     */
    public function validateUser(Authable $user, array $data): bool
    {
        return Hasher::verifyPassword($data["password"], $user->getUserHash());
    }

    /**
     * Attempt to build the requested model.
     *
     * @param string $model
     * @return Model
     */
    protected function buildModel(string $model): Model
    {
        if (class_exists($model = "\\" . ltrim($model, "\\"))) {
            return (new $model);
        }

        throw new InvalidArgumentException('The provided model doesnt exist');
    }
}
