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

namespace Twipsi\Components\Authentication\Drivers\Interfaces;

use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;

interface AuthDriverInterface
{
    /**
     * Check if we have an authenticated user.
     */
    public function check(): bool;

    /**
     * Check if we do not have an authenticated user.
     */
    public function guest(): bool;

    /**
     * Check if we have a user logged in already.
     */
    public function loggedIn(): bool;

    /**
     * Get the authenticated user.
     */
    public function user(): ?Authable;
}
