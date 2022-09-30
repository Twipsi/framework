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

use RuntimeException;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Twipsi\Foundation\ComponentManager;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Str;

class MailManager extends ComponentManager
{
    /**
     * Build the mailer transport driver.
     * 
     * @param string $driver
     * 
     * @return Mailer
     */
    protected function resolve(string $driver): Mailer
    {
        if (!($config = $this->app->config->get("mail.mailers." . $driver))) {
            throw new NotSupportedException(
                sprintf("No mail configuration found for driver [%s]", $driver)
            );
        }

        $mailer = new Mailer(
            $this->resolveCustomDriver($driver, $config) 
                ?? $this->createSymfonyTransporter($config),
            $this->app->get('view.factory'),
            $this->app->get('events')
        );

        if(!($config = $this->app->config->get("mail.addresses"))
        ) {
            throw new RuntimeException(
                sprintf("You havnt set any global addresses for driver [%s]", $driver)
            );
        }

        // Set global context addresses.
        $this->setGlobalAddresses($mailer, $config);

        return $mailer;
    }

    /**
     * Set all the global addresses to use
     * if no custom one has been set.
     * 
     * @param Mailer $mailer
     * @param ConfigRegistry $config
     * 
     * @return void
     */
    protected function setGlobalAddresses(Mailer $mailer, ConfigRegistry $config): void
    {
        $addresses = ['to', 'from', 'reply_to', 'return_path'];

        foreach($addresses as $type) {

            // Get address from configuration.
            if(! is_null($address = $config->get($type))) {

                $camelized = str_replace('_', '', Str::hay($type)->camelize('_'));
                $mailer->{'global'.$camelized}(...$address);
            }
        }
    }

    /**
     * Get the default driver set.
     * 
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app->config->get("mail.default");
    }

    /**
     * Create the symfony Transporter.
     * 
     * @param ConfigRegistry $config
     * 
     * @return TransportInterface
     */
    protected function createSymfonyTransporter(ConfigRegistry $config): TransportInterface
    {
        $transporter = $config->get('transporter');

        if ($transporter && 
            method_exists($this, $method = "create" . ucfirst($transporter) . "Transporter")) {
                
            return $this->{$method}($config);
        }

        throw new NotSupportedException(
            sprintf("Mailer transporter [%s] is not supported", $transporter)
        );
    }

    /**
     * Create the SMTP transport driver.
     * 
     * @param ConfigRegistry $config
     * 
     * @return TransportInterface
     */
    protected function createSmtpTransporter(ConfigRegistry $config): TransportInterface
    {
        $scheme = $config->get('encryption') === 'tls' 
            ? ($config->get('port') === 465 ? 'smtps' : 'smtp') 
            : '';

        return (new EsmtpTransportFactory())->create(
            new Dsn(
                $scheme,
                $config->get('host'),
                $config->get('username'),
                $config->get('password'),
                $config->get('port'),
                (array)$config
            )
        );
    }

    /**
     * Configure symfonys EsmtpTransport.
     * 
     * @param EsmtpTransport $transport
     * @param ConfigRegistry $config
     * 
     * @return EsmtpTransport
     */
    protected function configureSmtpTransport(EsmtpTransport $transport, ConfigRegistry $config): EsmtpTransport
    {
        $stream = $transport->getStream();

        if ($stream instanceof SocketStream) {
            if ($config->has('source_ip')) {
                $stream->setSourceIp($config->get('source_ip'));
            }

            if ($config->has('timeout')) {
                $stream->setTimeout($config->get('timeout'));
            }
        }

        return $transport;
    }

    /**
     * Create symfonys Mailgun transporter.
     * 
     * @param ConfigRegistry $config
     * 
     * @return MailgunApiTransport
     */
    protected function createMailgunTransporter(ConfigRegistry $config): MailgunApiTransport
    {
        return (new MailgunTransportFactory())->create(
            new Dsn(
                'mailgun+'.($config->get('scheme') ?? 'https'),
                $config->get('endpoint') ?? 'default',
                $config->get('secret'),
                $config->get('domain')
            )
        );
    }

    protected function createMailchimpTransporter(ConfigRegistry $config)
    {
    }

    protected function createGmailTransporter(ConfigRegistry $config)
    {
    }

}