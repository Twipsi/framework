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

namespace Twipsi\Components\User\Interfaces;

use Twipsi\Components\Model\Model;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;

interface IUserProvider
{
    /**
     * Retrieve a user by userID.
     *
     * @param int $id
     * @return Authable|null
     */
    public function getByID(int $id): ?Authable;

    /**
     * Retrieve a user by user token.
     *
     * @param int $id
     * @param string $token
     * @return Authable|null
     */
    public function getByToken(int $id, string $token): ?Authable;

    /**
     * Retrieve a user by user credentials.
     *
     * @param array $data
     * @return Authable|null
     */
    public function getByCredentials(array $data): ?Authable;

    /**
     * Update the (remember me) user token.
     *
     * @param Authable&Model $user
     * @param string $token
     * @return void
     */
    public function updateUserToken(Authable&Model $user, string $token): void;

    /**
     * Validate a user based on provided credentials.
     *
     * @param Authable $user
     * @param array $data
     * @return bool
     */
    public function validateUser(Authable $user, array $data): bool;
}
