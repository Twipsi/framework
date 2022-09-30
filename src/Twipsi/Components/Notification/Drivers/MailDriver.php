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

namespace Twipsi\Components\Notification\Drivers;

use RuntimeException;
use Symfony\Component\Mime\Email;
use Twipsi\Components\Mailer\MailManager;
use Twipsi\Components\Mailer\MailMessage as MailerMessage;
use Twipsi\Components\Mailer\Markdown;
use Twipsi\Components\Notification\Drivers\Interfaces\NotificationDriverInterface;
use Twipsi\Components\Notification\Interfaces\MailNotificationInterface as MailNotification;
use Twipsi\Components\Notification\Messages\MailMessage;
use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;
use Twipsi\Support\Str;

class MailDriver implements NotificationDriverInterface
{
    /**
     * Mail driver constructor
     */
    public function __construct(protected MailManager $mailer, protected Markdown $markdown) {}

    /**
     * Initiate mail sending.
     * 
     * @param Notifiable $notifiable
     * @param mixed $notification
     * 
     * @return void
     */
    public function send(Notifiable $notifiable, mixed $notification): void
    {
        // Get the message object.
        $message = $this->getMessage($notifiable, $notification);

        // Set the subject to the base message.
        $this->setSubject($message, $notification);

        // Build the message as the mailers message object and 
        // add all the data from the notifications mail message
        $mailerMessage = new MailerMessage(new Email(), $message);

        // Set the recipients from the notifiable.
        $mailerMessage->to(
            ...$this->getRecipients($notifiable)
        );

        $this->mailer->driver()->send(
            $this->buildView($message),
            $this->compileData($message, $notification),
            $mailerMessage
        );
    }

        /**
     * Get the message from the database notification.
     * 
     * @param Notifiable $notifiable
     * @param MailNotification $notification
     * 
     * @return MailMessage
     */
    public function getMessage(Notifiable $notifiable, MailNotification $notification): MailMessage
    {
        if(! is_null($message = $notification->toMail($notifiable))) {
            return $message;
        }

        throw new RuntimeException("No valid data provided by the mail notification");
    }

    /**
     * Build the message based on the view.
     * 
     * @param MailMessage $message
     * 
     * @return array
     */
    protected function buildView(MailMessage $message): array 
    {
        // If we have a custom view set for the mail return it. 
        // We will render the view at the mailer level.
        if(isset($message->view) && ! is_null($message->view)) {
            return $message->view;
        }

        // Set the mail theme to use if we have one while using templates.
        if(isset($message->theme) && ! is_null($message->theme)) {
            $this->markdown->theme($message->theme);
        }

        // Render the markdown template.
        $data = [$message->template, $message->data()];

        return [
            'html' => $this->markdown->render(...$data),
            'text' => $this->markdown->renderText(...$data),
        ];
    }

    /**
     * Add the subject to the message.
     * 
     * @param MailMessage $message
     * @param MailNotification $notification
     * 
     * @return void
     */
    protected function setSubject(MailMessage $message, MailNotification $notification): void 
    {
        $message->subject(
            $message->subject ?? 
            Str::hay(get_class($notification))->capitelize()
        );
    }

    /**
     * Compile the required data to be used.
     * 
     * @param MailMessage $message
     * @param MailNotification $notification
     * 
     * @return array
     */
    protected function compileData(MailMessage $message, MailNotification $notification): array 
    {
        return array_merge($message->data(), $this->buildSystemData($notification));
    }

    /**
     * Build extra system meta data.
     * 
     * @param MailNotification $notification
     * 
     * @return array
     */
    protected function buildSystemData(MailNotification $notification): array 
    {
        return [
            '__twipsi_notification_id' => $notification->id,
            '__twipsi_notification'    => get_class($notification),
        ];
    }

    /**
     * Get the recipients from the notifiable object
     * and add them to the message object. [email, $name]
     * 
     * @param Notifiable $notifiable
     * 
     * @return array
     */
    protected function getRecipients(Notifiable $notifiable): array
    {
        // If we have a string email address
        if(is_string($recipients = $notifiable->recipients('mail'))) {
            $recipients = [$recipients, null];
        }

        return $recipients;
    }
}
