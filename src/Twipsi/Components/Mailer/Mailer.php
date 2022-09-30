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

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Throwable;
use Twipsi\Components\Events\EventHandler;
use Twipsi\Components\Mailer\Events\MailFailedEvent;
use Twipsi\Components\Mailer\Events\MailSendingEvent;
use Twipsi\Components\Mailer\Events\MailSentEvent;
use Twipsi\Components\Mailer\MailMessage;
use Twipsi\Components\View\ViewFactory;

class Mailer
{
    /**
     * The global recipient.
     * 
     * @var array
     */
    protected array $to = [];

    /**
     * The global from address.
     * @var array
     */
    protected array $from = [];

    /**
     * The global replyTo addresses.
     * 
     * @var array
     */
    protected array $replyTo = [];

    /**
     * The global return path.
     * 
     * @var array
     */
    protected array $returnPath = [];

    /**
     * Contruct Mailer.
     * 
     * @param  TransportInterface $transporter
     * @param  protected ViewFactory $view
     * @param  protected EventHandler $event
     */
    public function __construct(
            protected TransportInterface $transporter, 
            protected ViewFactory $view, 
            protected EventHandler $event
    ){}

    /**
     * Global To address.
     * 
     * @param string $email
     * @param string $name
     * 
     * @return void
     */
    public function globalTo(string $email, string $name): void 
    {
        $this->from = [$email, $name];
    }

    /**
     * Global From address.
     * 
     * @param string $email
     * @param string $name
     * 
     * @return void
     */
    public function globalFrom(string $email, string $name): void 
    {
        $this->from = [$email, $name];
    }

    /**
     * Global Reply To address.
     * 
     * @param string $email
     * @param string $name
     * 
     * @return void
     */
    public function globalReplyTo(string $email, string $name): void 
    {
        $this->replyTo = [$email, $name];
    }

    /**
     * Global Return Path address.
     * 
     * @param string $email
     * @param string $name
     * 
     * @return void
     */
    public function globalReturnPath(string $email, string $name): void 
    {
        $this->from = [$email, $name];
    }

    /**
     * Dispatch the symfony mailer.
     * 
     * @param string $view
     * @param array $data
     * @param MailMessage|null $message
     * 
     * @return SentMessage|null
     */
    public function send(string|array $view, array $data = [], MailMessage $message = null): ?SentMessage
    {
        // Build the message item or use the provided and attach it 
        // to the data array so we can pass it to the view. 
        $data['message'] = $message = $this->buildMessage($message);

        // Set the contents to the message.
        $this->setContent($message, $data, ...$this->parseView($view));

        // Dispatch sending mail event.
        $this->event->dispatch(MailSendingEvent::class, $message);

        // Send the message using symfony mailer.
        try{
            $sentMessage = $this->sendMessage($message->getSymfonyMessage());

        } catch (Throwable $e) {

            // Dispatch message failed event.
            $this->event->dispatch(MailFailedEvent::class, $e);

            throw $e;
        }

        if($sentMessage) {
            
            // Dispatch message sent event.
            $this->event->dispatch(MailSentEvent::class, $sentMessage);

            return $sentMessage;
        }

        return null;
    }

    /**
     * Build the mail message and set default addresses.
     * 
     * @param MailMessage|null $message
     * 
     * @return MailMessage
     */
    protected function buildMessage(?MailMessage $message): MailMessage
    {
        // If we didint provied a mail message to the mailer.
        if(is_null($message)) {
            $message = new MailMessage(new Email);
        }

        // If we have a global from address set in the configuration
        // and we do not have any set in our mail message, we will
        // add the global address as the default address.
        if(! empty($this->from) && empty($message->from)) {
            $message->from(...$this->from);
        }

        if(! empty($this->replyTo) && empty($message->replyTo)) {
            $message->replyTo(...$this->replyTo);
        }

        if(! empty($this->returnPath)) {
            $message->returnPath($this->returnPath[0]);
        }

        return $message;
    }

    /**
     * Set the content to the message.
     * 
     * @param MailMessage $message
     * @param array $data
     * @param string|null $view
     * @param string|null $plain
     * @param string|null $raw
     * 
     * @return void
     */
    protected function setContent(MailMessage $message, array $data, ?string $view, ?string $plain, ?string $raw = null): void 
    {
        // We have an already rendered html.
        if($view != strip_tags($view)) {
            $message->html($view);

        // We have a view file provided.
        } elseif(! is_null($view)) {
            $message->html($this->renderMailFromView($view, $data) ?: ' ');
        }

        if(! is_null($plain)) {
            $message->text($plain);
        }

        if(! is_null($raw)) {
            $message->text($raw);
        }
    }

    /**
     * Parse the optionable view parameter.
     * 
     * @param string|array $view
     * 
     * @return array
     */
    protected function parseView(string|array $view): array 
    {
        return ! is_array($view) ? [$view, null, null] : [$view['html'], $view['text'], $view['raw'] ?? null]; 
    }

    /**
     * Render the mail content from a view file.
     * 
     * @param string $view
     * @param array $data
     * 
     * @return string
     */
    protected function renderMailFromView(string $view, array $data): string 
    {
        return $this->view->create($view, $data)->render();
    }

    /**
     * Send the message using symfony trnasporter.
     * 
     * @param Email $email
     * 
     * @return SentMessage|null
     */
    protected function sendMessage(Email $email): ?SentMessage
    {
        return $this->transporter->send($email, Envelope::create($email));
    }
}