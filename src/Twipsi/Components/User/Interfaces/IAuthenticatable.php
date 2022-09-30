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

interface IAuthenticatable
{
    /**
     * Retrieve the userID column name.
     *
     * @return string
     */
    public function getIDColumn(): string;

    /**
     * Retrieve the user token column name.
     *
     * @return string
     */
    public function getTokenColumn(): string;

    /**
     * Retrieve the user token column name.
     *
     * @return string
     */
    public function getHashColumn(): string;

    /**
     * Retrieve the user ID value.
     *
     * @return int|null
     */
    public function getUserID(): ?int;

    /**
     * Retrieve the user password hash value.
     *
     * @return string|null
     */
    public function getUserHash(): ?string;

    /**
     * Set the user hash.
     *
     * @param string $hash
     * @return void
     */
    public function setUserHash(string $hash): void;

    /**
     * Retrieve the user remember token.
     *
     * @return string|null
     */
    public function getRememberToken(): ?string;

    /**
     * Set the user remember token.
     *
     * @param string $token
     * @return void
     */
    public function setRememberToken(string $token): void;
}
