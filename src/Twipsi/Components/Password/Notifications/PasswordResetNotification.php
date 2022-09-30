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

namespace Twipsi\Components\Password\Notifications;

use Twipsi\Components\Notification\Exceptions\NotificationException;
use Twipsi\Components\Notification\Interfaces\MailNotificationInterface as MailNotification;
use Twipsi\Components\Notification\Messages\MailMessage;
use Twipsi\Components\Notification\Notification;
use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;
use Twipsi\Components\User\Interfaces\IResetable as Resetable;
use Twipsi\Facades\App;
use Twipsi\Facades\Translate;
use Twipsi\Facades\Url;

class PasswordResetNotification extends Notification implements MailNotification
{
    /**
     * Construct notification.
     */
    public function __construct(protected string $token)
    {
    }

    /**
    * The method that should be implemented to determine
    * which driver(s) we are using.

    * @param array|null $driver
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
                "We could not build the password reset url."
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
            ->subject(Translate::get("Password Reset Notification"))
            ->row(
                Translate::get(
                    "You have recieved this email because your user account has recieved a password reset request."
                )
            )
            ->row(
                Translate::get(
                    "To reset your password please follow the link provided below."
                )
            )
            ->action(Translate::get("Reset Your Password"), $url)
            ->row(
                Translate::get(
                    "Your password reset link will expire in :minutes minutes",
                    [
                        'minutes' => App::get("auth.password.manager")
                                        ->getConfiguredDriver()
                                        ?->get("expire")/60,
                    ]
                )
            )
            ->row(
                Translate::get(
                    "If you did not request a password reset for your account, contact our administrators."
                )
            );
    }

    /**
     * Build the url from the route item.
     */
    protected function buildUrl(Resetable $user): ?string
    {
        return Url::route("password.reset.index", [
            "token" => $this->token,
            "email" => $user->getEmailForPasswordReset(),
        ]);
    }
}
