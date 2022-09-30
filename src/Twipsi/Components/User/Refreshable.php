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

use Twipsi\Components\Authentication\Notifications\VerifyAccountNotification;
use Twipsi\Support\Chronos;

trait Refreshable
{
    /**
     * Name of the column that stores the date updated.
     * 
     * @var string
     */
    protected string $UPDATECOLUMN = "updated_at";

    /**
     * Retrieve the date of last update.
     * 
     * @return string|null
     */
    public function getUserUpdatedDate(): ?string
    {
        return $this->get($this->UPDATECOLUMN);
    }

    /**
     * Check if user has updated user data.
     * 
     * @return bool
     */
    public function hasUserUpdated(): bool
    {
        return !is_null($this->getUserUpdatedDate());
    }

    /**
     * Set the user update status to true.
     *
     * @return bool
     */
    public function markUserAsUpdated(): bool
    {
        return $this->fill([$this->UPDATECOLUMN, Chronos::date()->stamp()])
            ->save();
    }

    /**
     * Get the email that should be updated by.
     * 
     * @return string|null
     */
    public function getEmailForUserUpdate(): ?string
    {
        return $this->get("email");
    }

    /**
     * Send the notification to update user data.
     * 
     * @return void
     */
    public function sendUserUpdateNotification(): void
    {
        $this->notify(new VerifyAccountNotification());
    }
}
