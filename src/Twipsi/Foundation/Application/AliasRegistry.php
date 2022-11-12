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

namespace Twipsi\Foundation\Application;

use Twipsi\Support\Bags\SimpleBag as Container;

class AliasRegistry extends Container
{
    /**
     * Alias constructor.
     */
    public function __construct()
    {
        // Register all the core aliases, so we can use it
        // to build concrete classes when asked for.
        parent::__construct($this->coreAliases());
    }

    /**
     * Contains all the core aliases.
     *
     * @return array
     */
    protected function coreAliases(): array
    {
        return [
            'app' => \Twipsi\Foundation\Application\Application::class, //
            'auth.access' => \Twipsi\Components\Authorization\AccessManager::class,  //
            'auth.driver' => \Twipsi\Components\Authentication\Drivers\Interfaces\AuthDriverInterface::class,  //
            'auth.manager' => \Twipsi\Components\Authentication\AuthenticationManager::class,  //
            'auth.password.driver' => \Twipsi\Components\Password\Password::class,  //
            'auth.password.manager' => \Twipsi\Components\Password\PasswordManager::class,  //
            'config' => \Twipsi\Foundation\ConfigRegistry::class, //
            'console.app' => \Twipsi\Foundation\Console\Console::class,  //
            'console.schedule' => \Twipsi\Foundation\Console\CommandSchedule::class,  //
            'cookie' => \Twipsi\Components\Cookie\CookieBag::class, //
            'db.connector' => \Twipsi\Components\Database\DatabaseManager::class, //
            'db.connection' => \Twipsi\Components\Database\Interfaces\IDatabaseConnection::class, //
            'encrypter' => \Twipsi\Components\Security\Encrypter::class, //
            'events' => \Twipsi\Components\Events\EventHandler::class, //
            'mail.manager' => \Twipsi\Components\Mailer\MailManager::class, //
            'mail.mailer' => \Twipsi\Components\Mailer\Mailer::class, //
            'mail.markdown' => \Twipsi\Components\Mailer\Markdown::class, //
            'notification' => \Twipsi\Components\Notification\NotificationManager::class, //
            'ratelimiter' => \Twipsi\Components\RateLimiter\RateLimiter::class, //
            'redirector' => \Twipsi\Components\Url\Redirector::class, //
            'response' => \Twipsi\Components\Http\Response\ResponseFactory::class, //
            'request' => \Twipsi\Components\Http\HttpRequest::class, //
            'route.factory' => \Twipsi\Components\Router\RouteFactory::class, //
            'route.router' => \Twipsi\Components\Router\Router::class, //
            'route.routes' => \Twipsi\Components\Router\RouteBag::class, //
            'session.handler' => \Twipsi\Components\Session\SessionHandler::class, //
            'session.store' => \Twipsi\Components\Session\SessionItem::class, //
            'session.subscriber' => \Twipsi\Components\Session\SessionSubscriber::class, //
            'translator' => \Twipsi\Components\Translator\Translator::class, //
            'url' => \Twipsi\Components\Url\UrlGenerator::class, //
            'user' => \Twipsi\Bridge\User\ModelUser::class,  //
            'validator' => \Twipsi\Components\Validator\ValidatorFactory::class, //
            'view.cache' => \Twipsi\Components\View\ViewCache::class, //
            'view.factory' => \Twipsi\Components\View\ViewFactory::class, //
            'view.locator' => \Twipsi\Components\View\ViewLocator::class, //
        ];
    }

    /**
     * Attempt to find an alias of a registered class.
     *
     * @param string $abstract
     * @return bool
     */
    public function alias(string $abstract): bool
    {
        return !empty($this->search($abstract));
    }

    /**
     * Return the class(es) for a specific alias name.
     *
     * @param string $abstract
     * @return mixed
     */
    public function concrete(string $abstract): mixed
    {
        return $this->get($this->resolve($abstract), $abstract);
    }

    /**
     * Get the aliased abstract for a concrete.
     *
     * @param string $abstract
     * @return string|int
     */
    public function resolve(string $abstract): string|int
    {
        if ($this->has($abstract)) {
            return $abstract;
        }

        if ($result = $this->search($abstract)) {
            return $result;
        }

        return $abstract;
    }
}
