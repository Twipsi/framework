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
use Twipsi\Support\Bags\RecursiveArrayBag as Container;

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
     * If we have booted the laoders.
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
     * 
     * @return ComponentProvider
     */
    public function register(string|ComponentProvider $provider): ComponentProvider
    {
        // Get the laoder class name.
        $class = is_string($provider) ? $provider : get_class($provider);

        if($this->hasBeenLoaded($provider)) {
            return $this->getInstance($class);
        }

        // Register to application container incase we load it late.
        if(! $this->isApplicationComponentProvider($provider) 
            && ! $this->isFrameworkComponentProvider($provider)) {

            // Register the providers as source.
            if($this->isFrameworkType($provider)) {
                $this->push('framework', $provider);

            } else {
                $this->push('application', $provider);
            }
        }

        // Load the provider instance if its a class.
        $instance = is_string($provider) ? new $provider($this->app) : $provider;

        // Save to loaded and register provider.
        $this->set("loaded.{$class}", $instance);
        $instance->register($this->app);

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
        foreach($this->deferred() as $abstract => $provider) {
            $this->loadDeferredProvider($abstract);
        }
    }

    /**
     * Load a deferred component provider.
     * 
     * @param string $abstract
     * 
     * @return void
     */
    public function loadDeferredProvider(string $abstract): void 
    {
        // If its not a deferred provider exit.
        if(! $this->isDeferredAbstract($abstract)) {
            return;
        }

        // If it has been loaded already exit.
        if($this->isLoadedComponentProvider($provider = $this->get('deferred.'.$abstract))) {
            return;
        }

        $this->register($instance = new $provider($this->app));
        $this->bootProvider($instance);
    }

    /**
     * Run The boot method ont he provider.
     * 
     * @param ComponentProvider $provider
     * 
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
     * 
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
     * 
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
     * 
     * @return bool
     */
    public function isApplicationComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return ! is_null($this->search($provider, 'application'));
    }

    /**
     * Check if the component source is the framework.
     * 
     * @param string|ComponentProvider $provider
     * 
     * @return bool
     */
    public function isFrameworkComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return ! is_null($this->search($provider, 'framework'));
    }

    /**
     * Check if the component is always loaded.
     * 
     * @param string|ComponentProvider $provider
     * 
     * @return bool
     */
    public function isAlwaysComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return ! is_null($this->search($provider, 'always'));
    }

    /**
     * Check if the component is deferred.
     * 
     * @param string|ComponentProvider $provider
     * 
     * @return bool
     */
    public function isDeferredComponentProvider(string|ComponentProvider $provider): bool
    {
        if($provider instanceof ComponentProvider) {
            $provider = get_class($provider);
        }

        return ! is_null($this->search($provider, 'deferred'));
    }

    /**
     * Check if the abstract is deferred.
     * 
     * @param string $abstract
     * 
     * @return bool
     */
    public function isDeferredAbstract(string $abstract): bool
    {
        return $this->has('deferred.'.$abstract);
    }

    /**
     * Check if the component is loaded.
     * 
     * @param string|ComponentProvider $provider
     * 
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
     * Check if we have registered the component laoders.
     * 
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Check if we have booted the component laoders.
     * 
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Set the registered laoders to true;
     * 
     * @return void
     */
    public function setRegistered(): void 
    {
        $this->registered = true;
    }

    /**
     * Set the booted laoders to true;
     * 
     * @return void
     */
    public function setBooted(): void 
    {
        $this->booted = true;
    }

    /**
     * Check if its a framework component provider.
     * 
     * @param string $provider
     * 
     * @return bool
     */
    public function isFrameworkType(string $provider): bool
    {
        return str_starts_with($provider, 'Twipsi\\');
    }

}
