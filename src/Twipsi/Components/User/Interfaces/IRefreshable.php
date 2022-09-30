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

interface IRefreshable
{
    /**
     * Retrieve the date of last update.
     *
     * @return string|null
     */
    public function getUserUpdatedDate(): ?string;

    /**
     * Check if user has updated user data.
     *
     * @return bool
     */
    public function hasUserUpdated(): bool;

    /**
     * Set the user update status to true.
     *
     * @return bool
     */
    public function markUserAsUpdated(): bool;

    /**
     * Get the email that should be updated by.
     *
     * @return string|null
     */
    public function getEmailForUserUpdate(): ?string;

    /**
     * Send the notification to update user data.
     *
     * @return void
     */
    public function sendUserUpdateNotification(): void;
}
