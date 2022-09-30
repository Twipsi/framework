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

trait HandlesComponents
{
    /**
     * Component registry.
     * 
     * @var ComponentRegistry
     */
    protected ComponentRegistry $components;

    /**
     * The base component loaders.
     *
     * @var array
     */
    protected array $baseLoaders = [
        \Twipsi\Foundation\ComponentProviders\EventProvider::class,
        \Twipsi\Foundation\ComponentProviders\RouterProvider::class,
        \Twipsi\Foundation\ComponentProviders\ResponseProvider::class,
    ];

    /**
     * Load the base component providers.
     * 
     * @return void
     */
    protected function loadBaseComponents(): void 
    {
        $registry = new ComponentRegistry($this);

        foreach($this->baseLoaders as $loader) {
            $registry->register($loader);
            $registry->boot();
        }

        $this->setComponentRegistry($registry);
    }

    /**
     * Set the component registry.
     * 
     * @param ComponentRegistry $registry
     * 
     * @return void
     */
    public function setComponentRegistry(ComponentRegistry $registry): void 
    {
        $this->components = $registry;
    }

    /**
     * Get the component registry. 
     * 
     * @return ComponentRegistry
     */
    public function components(): ComponentRegistry
    {
        return $this->components;
    }

     /**
     * Load the deferred loader if needed.
     * 
     * @param string $abstract
     * 
     * @return void
     */
    protected function loadIfProviderIsDeferred(string $abstract): void 
    {
        if($this->components()->isDeferredAbstract($abstract) && 
            ! $this->instances->has($abstract)) {
                
                $this->components()->loadDeferredProvider($abstract);
            }
    }
}