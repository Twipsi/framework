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
use Twipsi\Components\User\Interfaces\IRefreshable as Refreshable;
use Twipsi\Facades\Config;
use Twipsi\Facades\Translate;
use Twipsi\Facades\Url;
use Twipsi\Support\Chronos;
use Twipsi\Support\Hasher;

class VerifyAccountNotification extends Notification implements MailNotification
{
    /**
    * The method that should be implemented to determine
    * which driver(s) we are using.

    * @param Notifiable $notifiable
    * 
    * @return array
    */
    public function via(Notifiable $notifiable): array
    {
        return ["mail"];
    }

    /**
     * Method to retrieve a valid MailMessage;
     * 
     * @param mixed $notifiable
     * 
     * @return MailMessage
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
     * 
     * @param string $url
     * 
     * @return MailMessage
     */
    protected function compileMailMessage(string $url): MailMessage
    {
        return (new MailMessage())
            ->subject(Translate::get("Account Verification Notification"))
            ->row(
                Translate::get(
                    "You have recieved this email because you are registered on our site."
                )
            )
            ->row(
                Translate::get(
                    "We would like to ask you to verify all your data so we can provide you with usefull information in the future."
                )
            )
            ->action(Translate::get("Verify Your Account"), $url)
            ->row(
                Translate::get(
                    "We thank you in advance for helping to keep the internet clean."
                )
            );
    }

    /**
     * Build the url from the route item.
     * 
     * @param Refreshable&Authenticatable $notifiable
     * 
     * @return string|null
     */
    protected function buildUrl(Refreshable&Authenticatable $notifiable): ?string
    {
        return Url::signed(
            "account.verify",
            [
                "id" => $notifiable->getUserID(),
                "token" => Hasher::hashFast(
                    $notifiable->getEmailForUserUpdate()
                ),
            ],
            Chronos::date()
                ->addMinutes(Config::get("auth.verification.expire", 1440))
                ->getDateTime()
        );
    }
}
