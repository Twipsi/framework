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

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twipsi\Components\Notification\Messages\MailMessage as Message;

class MailMessage
{
    use UsesNotification;

    /**
     * Construct mailer message.
     * 
     * @param  Email $message
     * @param  null|Message $notifyMessage
     */
    public function __construct(protected Email $message, protected ?Message $notifyMessage = null)
    {
        // If we have a mail message from the notification system,
        // merge it with the mailers mail message.
        if(! is_null($notifyMessage)) {
            $this->mergeNotificationMessage($notifyMessage);
        }
    }

    /**
     * Set the message subject.
     * 
     * @param string $subject
     * 
     * @return SimpleMessage
     */
    public function subject(string $subject): MailMessage
    {
        $this->message->subject($subject);

        return $this;
    }

    /**
     * Set the priority of the mail.
     * 
     * @param int $level
     * 
     * @return MailMessage
     */
    public function priority(int $level): MailMessage
    {
        $this->message->priority($level);

        return $this;
    }

    /**
     * Set the from address.
     * 
     * @param string $email
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function from(string $email, string $name = null): MailMessage
    {
        $this->message->from(new Address($email, $name));

        return $this;
    }

    /**
     * Set the sender address.
     * 
     * @param string $email
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function sender(string $email, string $name = null): MailMessage
    {
        $this->message->sender(new Address($email, $name));

        return $this;
    }

    /**
     * Set the recipients.
     * 
     * @param array|string $addresses
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function to(array|string $addresses, string $name = null): MailMessage
    {
        if(! is_array($addresses)) { 
            $this->message->to(new Address(...func_get_args()));

            return $this;
        }

        $this->message->to(...$this->buildAddressBag($addresses));
        
        return $this;
    }

    /**
     * Set the replyTo addresses.
     * 
     * @param array|string $addresses
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function replyTo(array|string $addresses, string $name = null): MailMessage
    {
        if(! is_array($addresses)) { 
            $this->message->replyTo(new Address(...func_get_args()));

            return $this;
        }

        $this->message->replyTo(...$this->buildAddressBag($addresses));
        
        return $this;
    }

    /**
     * Set the CC addresses.
     * 
     * @param array|string $addresses
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function cc(array|string $addresses, string $name = null): MailMessage
    {
        if(! is_array($addresses)) { 
            $this->message->cc(new Address(...func_get_args()));

            return $this;
        }

        $this->message->cc(...$this->buildAddressBag($addresses));
        
        return $this;
    }

    /**
     * Set the BCC addresses.
     * 
     * @param array|string $addresses
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function bcc(array|string $addresses, string $name = null): MailMessage
    {
        if(! is_array($addresses)) { 
            $this->message->bcc(new Address(...func_get_args()));

            return $this;
        }

        $this->message->bcc(...$this->buildAddressBag($addresses));
        
        return $this;
    }

    /**
     * Attach files to the mail.
     * 
     * @param FileItem|string $file
     * @param array $options
     * 
     * @return MailMessage
     */
    public function attach(string $file , array $options = []): MailMessage
    {
        $this->message->attachFromPath($file, $options['as'] ?? null, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Add raw data to the mail.
     * 
     * @param array $data
     * @param string $name
     * @param array $options
     * 
     * @return MailMessage
     */
    public function rawData(array $data , string $name, array $options = []): MailMessage
    {
        $this->message->attach($data, $name, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Return the symfony email message.
     * 
     * @return Email
     */
    public function getSymfonyMessage(): Email
    {
        return $this->message;
    }

    /**
     * Build all the addresses.
     * 
     * @param array $addresses
     * 
     * @return array
     */
    protected function buildAddressBag(array $addresses): array 
    {
        foreach($addresses as $address) {
            if(isset($address[1])) {
                $bag[] = new Address($address[0], $address[1]);
            } else {
                $bag[] = new Address($address[0]);
            }
        }

        return $bag ?? [];
    }

    /**
     * Call any Email object methods.
     * 
     * @param string $method
     * @param array $parameters
     * 
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed 
    {
        return $this->message->{$method}(...$parameters);
    }

    /**
     * Get any Email object properties.
     * 
     * @param string $property
     * 
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        return $this->message->{$property};
    }
}