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

namespace Twipsi\Components\Authentication\Notifications;

use Twipsi\Components\Notification\Exceptions\NotificationException;
use Twipsi\Components\Notification\Interfaces\MailNotificationInterface as MailNotification;
use Twipsi\Components\Notification\Messages\MailMessage;
use Twipsi\Components\Notification\Notification;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authenticatable;
use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;
use Twipsi\Components\User\Interfaces\IVerifiable as Verifiable;
use Twipsi\Facades\Config;
use Twipsi\Facades\Translate;
use Twipsi\Facades\Url;
use Twipsi\Support\Chronos;
use Twipsi\Support\Hasher;

class VerifyEmailNotification extends Notification implements MailNotification
{
    /**
     * Set the method of communication.
     */
    public function via(Notifiable $notifiable): array
    {
        return ["mail"];
    }

    /**
     * Send notification as mail message.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        if (!($url = $this->buildUrl($notifiable))) {
            throw new NotificationException(
                "We could not build the account activation url."
            );
        }

        return $this->compileMailMessage($url);
    }

    /**
     * Compile and build the message item.
     */
    protected function compileMailMessage(string $url): MailMessage
    {
        return (new MailMessage())
            ->subject(Translate::get("Account Activation Notification"))
            ->row(
                Translate::get(
                    "You have recieved this email because your email account has been used to register to our site."
                )
            )
            ->row(
                Translate::get(
                    "To activate your account please follow the link provided below."
                )
            )
            ->action(Translate::get("Activate Your Account"), $url)
            ->row(
                Translate::get(
                    "If you did not create this account, contact our administrators."
                )
            );
    }

    /**
     * Build the url from the route item.
     */
    protected function buildUrl(Verifiable&Authenticatable $user): ?string
    {
        return Url::signed(
            "account.activation.verify",
            [
                "id" => $user->getUserID(),
                "token" => Hasher::hashFast(
                    $user->getEmailToVerify()
                ),
            ],
            Chronos::date()
                ->addMinutes(Config::get("auth.activation.expire", 120))
                ->getDateTime()
        );
    }
}
