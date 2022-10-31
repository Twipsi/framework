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

use Twipsi\Foundation\Application;
use Twipsi\Foundation\Middleware;
use Twipsi\Support\Bags\ArrayBag as Container;

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
            'app' => Application\Application::class,
            'auth.manager' => \Twipsi\Components\Authentication\AuthenticationManager::class,
            'auth.driver' => \Twipsi\Components\Authentication\Drivers\Interfaces\AuthDriverInterface::class,
            'db.connector' => \Twipsi\Components\Database\DatabaseManager::class,
            'db.connection' => \Twipsi\Components\Database\Interfaces\IDatabaseConnection::class,
            'config' => \Twipsi\Foundation\ConfigRegistry::class,
            'cookie' => \Twipsi\Components\Cookie\CookieBag::class,
            'events' => \Twipsi\Components\Events\EventHandler::class,
            'mail.manager' => \Twipsi\Components\Mailer\MailManager::class,
            'mail.mailer' => \Twipsi\Components\Mailer\Mailer::class,
            'mail.markdown' => \Twipsi\Components\Mailer\Markdown::class,
            'encrypter' => \Twipsi\Components\Security\Encrypter::class,
            'notification' => \Twipsi\Components\Notification\NotificationManager::class,
            'middleware' => Middleware\MiddlewareHandler::class,
            'directory' => \Twipsi\Components\File\DirectoryManager::class,
            'route.router' => \Twipsi\Components\Router\Router::class,
            'route.factory' => \Twipsi\Components\Router\RouteFactory::class,
            'route.route' => \Twipsi\Components\Router\Route\Route::class,
            'route.routes' => \Twipsi\Components\Router\RouteBag::class,
            'url' => \Twipsi\Components\Url\UrlGenerator::class,
            'redirector' => \Twipsi\Components\Url\Redirector::class,
            'request' => \Twipsi\Components\Http\HttpRequest::class,
            'response' => \Twipsi\Components\Http\Response\ResponseFactory::class,
            'session.subscriber' => \Twipsi\Components\Session\SessionSubscriber::class,
            'session.handler' => \Twipsi\Components\Session\SessionHandler::class,
            'session.store' => \Twipsi\Components\Session\SessionItem::class,
            'translator' => \Twipsi\Components\Translator\Translator::class,
            'view.factory' => \Twipsi\Components\View\ViewFactory::class,
            'view.locator' => \Twipsi\Components\View\ViewLocator::class,
            'view.cache' => \Twipsi\Components\View\ViewCache::class,
        ];
    }

    /**
     * Attempt to find an alias.
     *
     * @param string $abstract
     * @return bool
     */
    public function alias(string $abstract): bool
    {
        return !empty($this->search($abstract));
    }

    /**
     * Return concrete for a process abstract.
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
     * @return mixed
     */
    public function resolve(string $abstract): mixed
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
