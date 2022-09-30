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

use Twipsi\Components\Authentication\Notifications\VerifyEmailNotification;
use Twipsi\Support\Chronos;

trait Verifiable
{
    /**
     * Name of the column that stores the date verified.
     * 
     * @var string
     */
    protected string $VERIFYCOLUMN = "email_verified_at";

    /**
     * Retrieve the date of verification.
     * 
     * @return string|null
     */
    public function getEmailVerifiedDate(): ?string
    {
        return $this->get($this->VERIFYCOLUMN);
    }

    /**
     * Check if user has verified the email.
     * 
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->getEmailVerifiedDate());
    }

    /**
     * Set the user email verification to true.
     * 
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        return $this->set($this->VERIFYCOLUMN, Chronos::date()->getDateTime())
            ->save();
    }

    /**
     * Get the email that should be verified.
     * 
     * @return string|null
     */
    public function getEmailToVerify(): ?string
    {
        return $this->get("email");
    }

    /**
     * Send the notification to verify email.
     * 
     * @return void
     */
    public function sendEmailVerifyNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}
