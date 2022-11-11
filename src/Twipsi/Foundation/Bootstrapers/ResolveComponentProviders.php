<?php

namespace Twipsi\Foundation\Bootstrapers;

use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Support\Bags\SimpleBag as Container;

trait ResolveComponentProviders
{
    /**
     * Build the cache and save it.
     *
     * @param array $providers
     * @return Container
     * @throws FileException
     */
    protected function buildComponentProviderCache(array $providers): Container
    {
        $repository = new Container([
            'providers' => $providers, 'loaded' => [],
            'framework' => [], 'application' => [],
            'always' => [], 'deferred' => []
        ]);

        foreach($providers as $provider) {

            $instance = new $provider($this->app);

            // Register the providers as source.
            if($this->app->components()->isFrameworkType($provider)) {
                $repository->push('framework', $provider);

            } else {
                $repository->push('application', $provider);
            }

            // If the loader is deferred register as deferred.
            if ($instance->deferred()) {
                foreach($instance->components() as $component) {

                    // Save the components it loads.
                    $repository->add("deferred", [$component => $provider]);
                }

            } else {
                $repository->push("always", $provider);
            }
        }

        return $repository;
    }
}