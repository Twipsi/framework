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

interface IResetable
{
    /**
     * Get the email that should receive password reset.
     *
     * @return string|null
     */
    public function getEmailForPasswordReset(): ?string;

    /**
     * Send the notification to reset password.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification(string $token): void;
}
