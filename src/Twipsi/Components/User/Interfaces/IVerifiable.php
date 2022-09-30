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

interface IVerifiable
{
    /**
     * Retrieve the date of verification.
     *
     * @return string|null
     */
    public function getEmailVerifiedDate(): ?string;

    /**
     * Check if user has verified the email.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool;

    /**
     * Set the user email verification to true.
     *
     * @return bool
     */
    public function markEmailAsVerified(): bool;

    /**
     * Get the email that should be verified.
     *
     * @return string|null
     */
    public function getEmailToVerify(): ?string;

    /**
     * Send the notification to verify email.
     *
     * @return void
     */
    public function sendEmailVerifyNotification(): void;
}
