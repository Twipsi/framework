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

use Twipsi\Foundation\ComponentProvider;
use Twipsi\Support\Arr;
use Twipsi\Support\Bags\ArrayBag as Container;

class ComponentRegistry extends Container
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * If we have registered the providers.
     *
     * @var bool
     */
    protected bool $registered = false;

    /**
     * If we have booted the loaders.
     *
     * @var bool
     */
    protected bool $booted = false;

    /**
     * Construct the registry.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct([]);

        $this->app = $app;
    }

    /**
     * Boot all the component providers.
     *
     * @return ComponentRegistry
     */
    public function boot(): ComponentRegistry
    {
        if($this->isBooted()) {
            return $this;
        }

        foreach($this->get('loaded') as $provider){
            $this->bootProvider($provider);
        }

        return $this;
    }

    /**
     * Register a component provider.
     *
     * @param string|ComponentProvider $provider
     * @return ComponentProvider
     */
    public function register(string|ComponentProvider $provider): ComponentProvider
    {
        // Get the loader class name.
        $class = is_string($provider) ? $provider : get_class($provider);

        if($this->hasBeenLoaded($provider)) {
            return $this->getInstance($class);
        }

        // Register to application container in case we load it late.
        if(! $this->isApplicationComponentProvider($provider)
            && ! $this->isFrameworkComponentProvider($provider)) {

            // Register the providers as source.
            $this->isFrameworkType($provider)
                ? $this->push('framework', $provider)
                : $this->push('application', $provider);
        }

        // Load the provider instance if it's a class.
        $instance = is_string($provider) ? new $provider($this->app) : $provider;

        // Save to loaded and register provider.
        $this->set("loaded.{$class}", $instance);
        $instance->register();

        // If we have booted the component providers then boot.
        if($this->isBooted()) {
            $this->bootProvider($instance);
        }

        return $instance;
    }

    /**
     * Load deferred component providers.
     *
     * @return void
     */
    public function loadDeferredComponentProviders(): void
    {
        Arr::loop($this->deferred(), function($provider, $abstract) {
            $this->loadDeferredProvider($abstract);
        });
    }

    /**
     * Load a deferred component provider.
     *
     * @param string $abstract
     * @return void
     */
    public function loadDeferredProvider(string $abstract): void
    {
        // If it's not a deferred provider exit.
        if(! $this->isDeferredAbstract($abstract)) {
            return;
        }

        $provider = Arr::get($this->deferred(), $abstract);

        // If it has been loaded already exit.
        if($this->isLoadedComponentProvider($provider)) {
            return;
        }

        $this->register($provider);
    }

    /**
     * Run The boot method on the provider.
     *
     * @param ComponentProvider $provider
     * @return void
     */
    public function bootProvider(ComponentProvider $provider): void
    {
        if(method_exists($provider, 'boot')) {
            call_user_func([$provider, 'boot']);
        }
    }

    /**
     * Get the provider components.
     *
     * @return array
     */
    public function providers(): array
    {
        return $this->get('providers', []);
    }

    /**
     * Get the framework components.
     *
     * @return array
     */
    public function framework(): array
    {
        return $this->get('framework', []);
    }

    /**
     * Get the application components.
     *
     * @return array
     */
    public function application(): array
    {
        return $this->get('application', []);
    }

    /**
     * Get the always loadable components.
     *
     * @return array
     */
    public function always(): array
    {
        return $this->get('always', []);
    }

    /**
     * Get the deferred components.
     *
     * @return array
     */
    public function deferred(): array
    {
        return $this->get('deferred', []);
    }

    /**
     * Get the loaded components.
     *
     * @return array
     */
    public function loaded(): array
    {
        return $this->get('loaded', []);
    }

    /**
     * Get the providers instance.
     *
     * @param string $provider
     * @return ComponentProvider|null
     */
    public function getInstance(string $provider): ?ComponentProvider
    {
        return $this->get("loaded.{$provider}");
    }

    /**
     * Check if a component has been loaded.
     *
     * @param string|ComponentProvider $provider
     * @return bool
     */
    public function hasBeenLoaded(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return $this->has("loaded.{$provider}");
    }

    /**
     * Check if the component source is the application.
     *
     * @param string|ComponentProvider $provider
     * @return bool
     */
    public function isApplicationComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return in_array($provider, $this->application());
    }

    /**
     * Check if the component source is the framework.
     *
     * @param string|ComponentProvider $provider
     * @return bool
     */
    public function isFrameworkComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return in_array($provider, $this->framework());
    }

    /**
     * Check if the component is always loaded.
     *
     * @param string|ComponentProvider $provider
     * @return bool
     */
    public function isAlwaysComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return in_array($provider, $this->always());
    }

    /**
     * Check if the component is deferred.
     *
     * @param string|ComponentProvider $provider
     * @return bool
     */
    public function isDeferredComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return in_array($provider, $this->deferred());
    }

    /**
     * Check if the abstract is deferred.
     *
     * @param string $abstract
     * @return bool
     */
    public function isDeferredAbstract(string $abstract): bool
    {
        return Arr::has($this->deferred(), $abstract);
    }

    /**
     * Check if the component is loaded.
     *
     * @param string|ComponentProvider $provider
     * @return bool
     */
    public function isLoadedComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return $this->has("loaded.{$provider}");
    }

    /**
     * Check if we have registered the component providers.
     *
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Check if we have booted the component providers.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Set the status of registered providers to true;
     *
     * @return void
     */
    public function setRegistered(): void
    {
        $this->registered = true;
    }

    /**
     * Set the status of booted providers to true;
     *
     * @return void
     */
    public function setBooted(): void
    {
        $this->booted = true;
    }

    /**
     * Check if it's a framework component provider.
     *
     * @param string $provider
     * @return bool
     */
    public function isFrameworkType(string $provider): bool
    {
        return str_starts_with($provider, 'Twipsi\\');
    }
}
