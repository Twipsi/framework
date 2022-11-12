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
    protected function buildComponentProviderRepository(array $providers): Container
    {
        $repository = new Container([
            'providers' => $providers, 'loaded' => [],
            'framework' => [], 'application' => [],
            'always' => [], 'deferred' => []
        ]);

        foreach($providers as $provider) {
            $instance = new $provider($this->app);

            // Register the providers as source.
            $this->app->components()->isFrameworkType($provider)
                ? $repository->push('framework', $provider)
                : $repository->push('application', $provider);

            // If the loader is deferred register as deferred.
            if ($instance->deferred()) {
                array_map(function($component) use($repository, $provider) {
                    $repository->add("deferred", [$component => $provider]);
                }, $instance->components());

            } else {
                $repository->push("always", $provider);
            }
        }

        return $repository;
    }
}