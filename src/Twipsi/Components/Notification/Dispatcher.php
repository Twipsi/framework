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

namespace Twipsi\Components\Notification;

use Twipsi\Components\Notification\Events\NotificationSentEvent;
use Twipsi\Components\Queue\ShouldQueue;
use Twipsi\Components\User\Interfaces\ILocalizable;
use Twipsi\Components\User\Interfaces\INotifiable as Notifiable;
use Twipsi\Facades\Event;
use Twipsi\Support\KeyGenerator;
use Twipsi\Support\Traits\Localizable;

class Dispatcher
{
    use Localizable;

    /**
     * Construct notification dispatcher.
     * 
     * @param  protected NotificationManager $manager
     * @param  protected protected null|string $locale
     */
    public function __construct(
        protected NotificationManager $manager, 
        protected ?string $locale = null
    ){}

    /**
     * Send the notification to all notifiables.
     * 
     * @param array $notifiables
     * @param Notification $notification
     * 
     * @return void
     */
    public function send(array $notifiables, Notification $notification): void 
    {
        if($notification instanceof ShouldQueue) {
            $this->queueNotification($notifiables, $notification);

        } else {
            $this->sendNow($notifiables, $notification);
        }
    }

    /**
     * Send the notification to all notifiables immedietly.
     * 
     * @param array $notifiables
     * @param Notification $notification
     * 
     * @return void
     */
    public function sendNow(array $notifiables, Notification $notification): void 
    {
        foreach($notifiables as $notifiable) {

            if(empty($channels = $notification->via($notifiable))) {
                continue;
            }

            $callback = function() use ($channels, $notifiable, $notification) {
                $id = KeyGenerator::generateUUIDKey();

                foreach($channels as $channel) {
                    $this->sendToNotifiable($notifiable, $notification, $id, $channel);
                }
            };

            $this->withLocale($this->preferredLocale($notifiable, $notification), $callback);
        }
    }

    /**
     * Send the notification to a notifiable.
     * 
     * @param Notifiable $notifiable
     * @param string $id
     * @param string $channel
     * 
     * @return void
     */
    protected function sendToNotifiable(Notifiable $notifiable, Notification $notification, string $id, string $channel): void 
    {
        $notification->id = $notification->id ?? $id;

        $this->manager->driver($channel)->send($notifiable, $notification);

        // Dispatch Login event
        Event::dispatch(NotificationSentEvent::class, $notifiable, $notification, $channel);
    }

    /**
     * Attempt to resolve the preffered locale.
     * 
     * @param Notifiable $notifiable
     * @param Notification $notification
     * 
     * @return string|null
     */
    public function preferredLocale(Notifiable $notifiable, Notification $notification): ?string
    {
        return $notification->locale ?? $this->locale ??
        ($notifiable instanceof ILocalizable ? $notifiable->getLocale() : null);
    }

    /**
     * Queue the notifications to a worker.
     * 
     * @param array $notifiables
     * @param Notification $notification
     * 
     * @return void
     */
    protected function queueNotification(array $notifiables, Notification $notification): void 
    {
        //To Implement after queueing.
        return;
    }
}


