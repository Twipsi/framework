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

use Twipsi\Bridge\User\User as User;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Support\Hasher;
use Twipsi\Support\Str;

final class DatabaseUserProvider implements IUserProvider
{
    /**
     * Database connection.
     *
     * @var IDatabaseConnection
     */
    protected IDatabaseConnection $connection;

    /**
     * Database table name.
     *
     * @var string
     */
    protected string $table;

    /**
     * UserProvider Constructor.
     *
     * @param IDatabaseConnection $connection
     * @param string $table
     */
    public function __construct(IDatabaseConnection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Retrieve a user by userID.
     *
     * @param int $id
     * @return Authable|null
     */
    public function getByID(int $id): ?Authable
    {
        if (!($user = $this->connection->open($this->table)->find($id))) {
            return null;
        }

        return new User((array) $user);
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
        $user = $this->getByID($id);

        if ($user && ($remember = $user->getRememberToken())) {
            return Hasher::checkHash($remember, $token) ? $user : null;
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
        $table = $this->connection->open($this->table);

        // Remove the password field because it won't match with the saved hash.
        $credentials = array_filter($data,
            function ($k) {
                return !Str::hay($k)->resembles("password");
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($credentials as $name => $credential) {
            $table->where($name, "=", $credential);
        }

        $user = $table->first();

        return $user ? new User((array) $user) : null;
    }

    /**
     * Update the (remember me) user token.
     *
     * @param Authable $user
     * @param string $token
     * @return void
     */
    public function updateUserToken(Authable $user, string $token): void
    {
        $user->setRememberToken($token);

        $this->connection
            ->open($this->table)
            ->where($user->getIDColumn(), "=", $user->getUserID())
            ->update([$user->getTokenColumn() => $token]);
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
}
