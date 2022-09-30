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

namespace Twipsi\Components\Mailer;

use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Twipsi\Components\Notification\Messages\MailMessage as NotificationMessage;

trait UsesNotification
{
    /**
     * Merge the notificaiton mail message with the mailer message.
     * 
     * @param NotificationMessage $message
     * 
     * @return void
     */
    protected function mergeNotificationMessage(NotificationMessage $message): void
    {
        $this->mergeAddresses($message);

        $this->mergeSubject($message);

        $this->mergeAttachments($message);

        $this->mergeAdditional($message);
    }

    /**
     * Add all the addresses required to the mailer message. 
     * 
     * @param NotificationMessage $message
     * 
     * @return void
     */
    protected function mergeAddresses(NotificationMessage $message): void 
    {
        // Set from message.
        if(!empty($message->from)) {
            $this->from(...$message->from);
        }

        // Set reply to addresses.
        if(!empty($message->replyTo)) {
            foreach($message->replyTo as $address) {
                $this->replyTo(...$address);
            }
        }

        // Set recipients.
        if(!empty($message->to)) {
            $this->to($message->to);
        }

        // Set CC addresses. 
        if(!empty($message->cc)) {
            foreach($message->cc as $address) {
                $this->cc(...$address);
            }
        }

        if(!empty($message->bcc)) {
            foreach($message->bcc as $address) {
                $this->bcc(...$address);
            }
        }
    }

    /**
     * Add the subject to the message.
     * 
     * @param NotificationMessage $message
     * 
     * @return void
     */
    protected function mergeSubject(NotificationMessage $message): void 
    {
        $this->subject(
            $message->subject ?? 'Twipsi Notification'
        );
    }

    /**
     * Add the attachments to the message.
     * 
     * @param NotificationMessage $message
     * 
     * @return void
     */
    protected function mergeAttachments(NotificationMessage $message): void 
    {
        foreach($message->attachments as $attachment) {
            $this->attach(...$attachment);
        }

        foreach($message->rawAttachments as $attachment) {
            $this->attachData(...$attachment);
        }
    }

    /**
     * Add additional data tot he message.
     * 
     * @param NotificationMessage $message
     * 
     * @return void
     */
    protected function mergeAdditional(NotificationMessage $message): void 
    {
        if(!empty($message->priority)) {
            $this->priority($message->priority);
        }

        if(!empty($message->tags)) {
            foreach ($message->tags as $tag) {
                $this->getHeaders()->add(new TagHeader($tag));
            }
        }

        if(!empty($message->meta)) {
            foreach ($message->meta as $key => $value) {
                $this->getHeaders()->add(new MetadataHeader($key, $value));
            }
        }
    }

}