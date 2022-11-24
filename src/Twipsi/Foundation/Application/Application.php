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

use Closure;
use ReflectionException;
use Twipsi\Components\File\FileBag;
use Twipsi\Components\Http\Exceptions\HttpException;
use Twipsi\Components\Http\Exceptions\NotFoundHttpException;
use Twipsi\Facades\Facade;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class Application extends IOCManager
{
    use HandlesLocale, HandlesEnvironment, HandlesPaths, HandlesComponents, HandlesBootstrap;

    /**
     * System version
     *
     * @var string
     */
    public const version = '1.0.0';

    /**
     * The current context.
     *
     * @var Closure|null
     */
    protected ?Closure $context = null;

    /**
     * The application namespace.
     *
     * @var string
     */
    protected string $namespace;

    /**
     * Terminator callbacks collection.
     *
     * @var array
     */
    protected array $terminators = [];

    /**
     * Application constructor.
     *
     * @param string $basePath
     */
    public function __construct(public string $basePath = '')
    {
        parent::__construct();

        $this->setBasePaths($basePath);

        $this->registerApplication();

        $this->loadBaseComponents();

        $this->loadHelpers();

        $this->namespace = '\\App';
    }

    /**
     * Get system version.
     *
     * @return string
     */
    public function version(): string
    {
        return self::version;
    }

    /**
     * Set the application instance and its paths.
     *
     * @return void
     */
    protected function registerApplication(): void
    {
        $this->instances->set('app', $this);
        $this->instances->set(Application::class, $this);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this);
    }

    /**
     * Return the configuration cache file.
     *
     * @return string
     */
    public function configurationCacheFile(): string
    {
        $path = !is_null($context = $this->getContext())
            ? 'config.'.$context.'.php'
            : 'config.php';

        return $this->nav()
            ->bootPath('cache/'.$path);
    }

    /**
     * Check if the configuration is cached.
     *
     * @return bool
     */
    public function isConfigurationCached(): bool
    {
        return is_file($this->configurationCacheFile());
    }

    /**
     * Return the routes cache file.
     *
     * @return string
     */
    public function routeCacheFile(): string
    {
        return $this->nav()
            ->bootPath('cache/routes.php');
    }

    /**
     * Check if the routes are cached.
     *
     * @return bool
     */
    public function isRoutesCached(): bool
    {
        return is_file($this->routeCacheFile());
    }

    /**
     * Return the components cache file.
     *
     * @return string
     */
    public function componentCacheFile(): string
    {
        $path = !is_null($context = $this->getContext())
            ? 'components.'.$context.'.php'
            : 'components.php';

        return $this->nav()
            ->bootPath('cache/'.$path);
    }

    /**
     * Check if the components are cached.
     *
     * @return bool
     */
    public function isComponentsCached(): bool
    {
        return is_file($this->componentCacheFile());
    }

    /**
     * Return the events cache file.
     *
     * @return string
     */
    public function eventsCacheFile(): string
    {
        return $this->nav()
            ->bootPath('cache/events.php');
    }

    /**
     * Check if the events are cached.
     *
     * @return bool
     */
    public function isEventsCached(): bool
    {
        return is_file($this->eventsCacheFile());
    }

    /**
     * Set the current context.
     *
     * @param Closure $loader
     * @return void
     */
    public function setContext(Closure $loader): void
    {
        $this->context = $loader;
    }

    /**
     * Get the current context.
     *
     * @return string|null
     */
    public function getContext(): ?string
    {
        // If we haven't poststrapped we don't know the route context,
        // so we return the default context.
        if($this->isPoststrapped() || $this->isTest()) {
            return !is_null($this->context)
                ? call_user_func($this->context, $this)
                : null;
        }

        return null;
    }

    /**
     * Check if we are in debug mode.
     *
     * @return bool
     * @throws ApplicationManagerException|ReflectionException
     */
    public function isDebugEnabled(): bool
    {
        return (bool)$this->get('config')
            ->get('system.debug');
    }

    /**
     * Make or load a class while resolving DI.
     *
     * @param string|object $abstract
     * @param array<int,mixed> $parameters
     * @return mixed
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function make(string|object $abstract, array $parameters = []) : mixed
    {
        if(is_object($abstract)) {
            return $abstract;
        }

        // Get the abstract pointer if there are any registers as an alias.
        $this->loadIfProviderIsDeferred($abstract = $this->aliases->resolve($abstract));

        return parent::make($abstract, $parameters);
    }

    /**
     * Check if the abstract is bound already.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return $this->components()->isDeferredAbstract($abstract)
            || parent::bound($abstract);
    }

    /**
     * Return alias registry.
     *
     * @return AliasRegistry
     */
    public function aliases(): AliasRegistry
    {
        return $this->aliases;
    }

    /**
     * Return instance registry.
     *
     * @return InstanceRegistry
     */
    public function instances(): InstanceRegistry
    {
        return $this->instances;
    }

    /**
     * Return implant registry.
     *
     * @return ImplantRegistry
     */
    public function implants(): ImplantRegistry
    {
        return $this->implants;
    }

    /**
     * Return binding registry.
     *
     * @return BindingRegistry
     */
    public function bindings(): BindingRegistry
    {
        return $this->bindings;
    }

    /**
     * Return rebinding callbacks.
     *
     * @return array<string,array<int|Closure>>
     */
    public function rebindings(): array
    {
        return $this->bindings->rebindings;
    }

    /**
     * Return the extension callbacks.
     *
     * @return array
     */
    public function extensions(): array
    {
        return $this->bindings->extensions;
    }

    /**
     * Get the application instance.
     *
     * @return Application
     */
    public function getInstance(): Application
    {
        return $this;
    }

    /**
     * Check if system is down for maintenance.
     *
     * @return bool
     * @throws ApplicationManagerException|ReflectionException
     */
    public function isUnderMaintenance(): bool
    {
        return $this->get('config')
            ->get('system.maintenance');
    }

    /**
     * Load all system helper functions.
     *
     * @return void
     */
    protected function loadHelpers(): void
    {
        (new FileBag($this->paths->get('path.helpers'), 'php'))
            ->includeAll();
    }

    /**
     * Exit the application with a response.
     *
     * @param int $code
     * @param string $message
     * @param array<int,string> $headers
     *
     * @return void
     */
    public function exit(int $code, string $message = "", array $headers = []): void
    {
        $code === 404
            ? throw new NotFoundHttpException($message)
            : throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    public function applicationNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Set the application namespace.
     *
     * @param string $namespace
     * @return void
     */
    public function setApplicationNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Flush the application.
     *
     * @return void
     */
    public function flush(): void
    {
        foreach (get_object_vars($this) as $property => $value) {

            if(is_array($value)) {
                $this->{$property} = [];
            } else {
                unset($this->{$property});
            }
        }
    }

    /**
     * Register a terminator callback.
     *
     * @param Closure $callback
     * @return $this
     */
    public function terminator(Closure $callback): Application
    {
        $this->terminators[] = $callback;

        return $this;
    }

    /**
     * Terminate the application.
     *
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function terminate(): void
    {
        $this->flush();

        // Run the terminator callbacks if any.
        foreach ($this->terminators as $terminator) {
            $this->build($terminator);
        }
    }
}
