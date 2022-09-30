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

namespace Twipsi\Components\Notification\Messages;

use Twipsi\Components\Notification\Messages\Message;
use Twipsi\Components\File\FileItem;

class MailMessage extends Message
{
    /**
     * The priority of the mail.
     * 
     * @var int
     */
    public int $priority;

    /**
     * The view theme to use for the mail.
     * 
     * @var string
     */
    public string $view;

    /**
     * Any data to be added to the view.
     * 
     * @var array
     */
    public array $viewData = [];

    /**
     * The mail theme to use.
     * 
     * @var string
     */
    public string $theme;

    /**
     * The email tempalte to use.
     * 
     * @var string
     */
    public string $template = 'notifications.mail';

    /**
     * The recipients of the message.
     * 
     * @var array
     */
    public array $to = [];

    /**
     * The from address (email, $name).
     * 
     * @var array
     */
    public array $from = [];

    /**
     * The replyTo addresses.
     * 
     * @var array
     */
    public array $replyTo = [];

    /**
     * The CC addresses.
     * 
     * @var array
     */
    public array $cc = [];

    /**
     * The BCC addresses.
     * 
     * @var array
     */
    public array $bcc = [];

    /**
     * THe mail attachments.
     * 
     * @var array
     */
    public array $attachments = [];

    /**
     * The raw datas for the mail.
     * 
     * @var array
     */
    public array $rawAttachments = [];

    /**
     * Any mail tags.
     * 
     * @var array
     */
    public array $tags = [];

    /**
     * Mail meta datas.
     * 
     * @var array
     */
    public array $meta = [];

    /**
     * Set the priority of the mail.
     * 
     * @param int $level
     * 
     * @return MailMessage
     */
    public function priority(int $level): MailMessage
    {
        $this->priority = $level;

        return $this;
    }

    /**
     * Set the view theme for the mail.
     * 
     * @param string $view
     * @param array $data
     * 
     * @return MailMessage
     */
    public function view(string $view, array $data = []): MailMessage 
    {
        $this->view = $view;
        $this->viewData = $data;

        // We wont need a template if we have a view.
        $this->template = null;

        return $this;
    }

    /**
     * Set the template to use.
     * 
     * @param string $template
     * @param array $data
     * 
     * @return MailMessage
     */
    public function template(string $template, array $data = []): MailMessage 
    {
        $this->template = $template;
        $this->viewData = $data;

        // We wont need a view if we have a template.
        $this->template = null;

        return $this;
    }

    /**
     * Set the mail theme to use.
     * 
     * @param string $theme
     * 
     * @return MailMessage
     */
    public function theme(string $theme): MailMessage 
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Set the message recipients.
     * 
     * @param array $recipients
     * 
     * @return MailMessage
     */
    public function to(array $recipients): MailMessage
    {
        $this->to = $recipients;

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
        $this->from = [$email, $name];

        return $this;
    }

    /**
     * Set the reply to addresses.
     * 
     * @param array|string $addresses
     * @param string|null $name
     * 
     * @return MailMessage
     */
    public function replyTo(array|string $addresses, string $name = null): MailMessage
    {
        if(! is_array($addresses)) {
            $this->replyTo[] = [$addresses, $name];
        } else {
            $this->replyTo += $addresses;
        }

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
            $this->cc[] = [$addresses, $name];
        } else {
            $this->cc += $addresses;
        }

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
            $this->bcc[] = [$addresses, $name];
        } else {
            $this->bcc += $addresses;
        }

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
    public function attach(FileItem|string $file , array $options = []): MailMessage
    {
        if($file instanceof FileItem) {
            $file = $file->getPath();
        }

        $this->attachments[] = compact($file, $options);

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
        $this->rawAttachments[] = compact('data', 'name', 'options');

        return $this;
    }

    /**
     * Add tags to the mail.
     * 
     * @param string $value
     * 
     * @return MailMessage
     */
    public function tag(string $value): MailMessage
    {
        $this->tags[] = $value;

        return $this;
    }

    /**
     * Add meta data to the mail.
     * 
     * @param string $key
     * @param string $value
     * 
     * @return MailMessage
     */
    public function meta(string $key, string $value): MailMessage
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Return all the message data.
     * 
     * @return array
     */
    public function data(): array 
    {
        return array_merge($this->toArray(true), $this->viewData);
    }
}