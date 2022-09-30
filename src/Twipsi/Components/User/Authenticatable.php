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

trait Authenticatable
{
    /**
     * Name of the column that stores the user ID.
     * 
     * @var string
     */
    protected string $IDCOLUMN = "id";

    /**
     * Name of the column that stores the user remember token.
     * 
     * @var string
     */
    protected string $TOKENCOLUMN = "remember_token";

    /**
     * Name of the column that stores the user hash.
     * 
     * @var string
     */
    protected string $HASHCOLUMN = "password";

    /**
     * Retrieve the userID column name.
     * 
     * @return string
     */
    public function getIDColumn(): string
    {
        return $this->IDCOLUMN;
    }

    /**
     * Retrieve the user token column name.
     * 
     * @return string
     */
    public function getTokenColumn(): string
    {
        return $this->TOKENCOLUMN;
    }

    /**
     * Retrieve the user token column name.
     * 
     * @return string
     */
    public function getHashColumn(): string
    {
        return $this->HASHCOLUMN;
    }

    /**
     * Retrieve the user ID value.
     * 
     * @return int|null
     */
    public function getUserID(): ?int
    {
        return (int) $this->get($this->IDCOLUMN);
    }

    /**
     * Retrieve the user password hash value.
     * 
     * @return string|null
     */
    public function getUserHash(): ?string
    {
        return $this->get($this->HASHCOLUMN);
    }

    /**
     * Set the user hash.
     * 
     * @param string $hash
     * @return void
     */
    public function setUserHash(string $hash): void
    {
        $this->set($this->HASHCOLUMN, $hash);
    }

    /**
     * Retrieve the user remember token.
     * 
     * @return string|null
     */
    public function getRememberToken(): ?string
    {
        return $this->get($this->TOKENCOLUMN);
    }

    /**
     * Set the user remember token.
     * 
     * @param string $token
     * @return void
     */
    public function setRememberToken(string $token): void
    {
        $this->set($this->TOKENCOLUMN, $token);
    }
}
