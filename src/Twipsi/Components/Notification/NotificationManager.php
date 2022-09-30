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

use Twipsi\Components\Notification\Drivers\BroadcastDriver;
use Twipsi\Components\Notification\Drivers\DatabaseDriver;
use Twipsi\Components\Notification\Drivers\Interfaces\NotificationDriverInterface;
use Twipsi\Components\Notification\Drivers\MailDriver;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\NotSupportedException;

class NotificationManager
{
    /**
     * The current locale to use.
     * 
     * @var string
     */
    protected ?string $locale = null;

    /**
     * The default driver to fallback.
     * 
     * @var string
     */
    protected string $default = 'mail';

    /**
     * The notification drivers container.
     * 
     * @var array
     */
    protected array $drivers;

    /**
     * Construct notification manager.
     * 
     * @param  protected Application $app
     */
    public function __construct(protected Application $app){}

    /**
     * Initiate dispatcher and send to all required channels.
     * 
     * @param array $notifiable
     * @param Notification $notification
     * 
     * @return void
     */
    public function send(array $notifiables, Notification $notification): void 
    {
        (new Dispatcher($this, $this->locale))
            ->send($notifiables, $notification);
    }

    /**
     * Initiate dispatcher and send to all required channels immedietly.
     * 
     * @param array $notifiable
     * @param Notification $notification
     * 
     * @return void
     */
    public function sendNow(array $notifiables, Notification $notification): void 
    {
        (new Dispatcher($this, $this->locale))
            ->sendNow($notifiables, $notification);
    }

    /**
     * Returns the current notification driver.
     * 
     * @param string|null $name
     * 
     * @return NotificationDriverInterface
     */
    public function driver(string $name = null): NotificationDriverInterface
    {
        $name = $name ?? $this->getDefaultDriver();

        // Save the drivers in an array to be accessible later without
        // rebuilding them, while also being able to build another driver version
        return $this->drivers[$name] ??
            ($this->drivers[$name] = $this->resolve($name));
    }

    /**
     * Build the notification driver.
     * 
     * @param string $driver
     * 
     * @return NotificationDriverInterface
     */
    protected function resolve(string $driver): NotificationDriverInterface
    {
        if (
            method_exists(
                $this,
                $method = "create" . ucfirst($driver) . "Driver"
            )
        ) {
            return $this->{$method}();
        }

        throw new NotSupportedException(
            sprintf(
                "Notification driver [%s] is not supported",
                $driver
            )
        );
    }

    /**
     * Create mail based notification driver.
     * 
     * @return NotificationDriverInterface
     */
    protected function createMailDriver(): NotificationDriverInterface
    {
        return $this->app->make(MailDriver::class);
    }

    /**
     * Create database based notification driver.
     * 
     * @return NotificationDriverInterface
     */
    protected function createDatabaseDriver(): NotificationDriverInterface
    {
        return $this->app->make(DatabaseDriver::class);
    }

    /**
     * Create broadcast based notification driver.
     * 
     * @return NotificationDriverInterface
     */
    protected function createBroadcastDriver(): NotificationDriverInterface
    {
        return $this->app->make(BroadcastDriver::class);
    }

    /**
     * Get the default driver set.
     * 
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->default;
    }

    /**
     * Set the delivery driver name.
     * 
     * @param string $driver
     * 
     * @return void
     */
    public function deliverVia(string $driver): void 
    {
        $this->default = $driver;
    }

    /**
     * Check the delivering driver name.
     * 
     * @return string
     */
    public function deliversVia(): string
    {
        return $this->getDefaultDriver();
    }

    /**
     * Set the locale to be used for notifications.
     * 
     * @param string $locale
     * 
     * @return NotificationManager
     */
    public function locale(string $locale): NotificationManager
    {
        $this->locale = $locale;

        return $this;
    }
}