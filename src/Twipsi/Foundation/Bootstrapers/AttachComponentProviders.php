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

namespace Twipsi\Foundation\Bootstrapers;

use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileBag;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Application\Application;
use Twipsi\Support\Bags\SimpleBag as Container;

class AttachComponentProviders
{
    /**
     * The cache path we should use.
     * 
     * @var string
     */
    protected string $cache;

    /**
     * Construct Bootstrapper.
     */
    public function __construct(protected Application $app){}

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws FileException
     */
    public function invoke(): void
    {
        $loaders = $this->app->get('config')->get('component.loaders')->all();

        $this->setCachePath($this->app->componentCacheFile());
        $this->load($loaders);

        $this->app->components()->setRegistered();
    }

    /**
     * Load all the component providers.
     *
     * @param array $providers
     * @return void
     * @throws FileException
     */
    public function load(array $providers): void
    {
        $cache = $this->getComponentProviderCache();

        // If we need to rebuild the cache then rebuild it.
        if($this->shouldRebuildCache($cache, $providers)) {
            $cache = $this->buildComponentProviderCache($providers);
        }

        $cache = is_array($cache) ? new Container($cache) : $cache;

        // Merge the cache data into the registry.
        $this->app->components()->inject($cache->all());

        // Run through the cache and load providers.
        foreach($cache->get('always') as $provider) {
            $this->app->components()->register($provider);
        }
    }

    /**
     * Get the cache file.
     * 
     * @return array|null
     */
    protected function getComponentProviderCache(): ?array
    {
        try {
            if(! $file = (new FileItem($this->cache))->include()) {
                throw new FileNotFoundException();
            }

            return $file;

        } catch(FileNotFoundException) {
            return ['providers' => []];
        }
    }

    /**
     * Check if we should rebuild the cache or not.
     * 
     * @param array $cache
     * @param array $providers
     * @return bool
     */
    protected function shouldRebuildCache(array $cache, array $providers): bool 
    {
        return empty($cache) || $cache['providers'] != $providers;
    }

    /**
     * Build the cache and save it.
     *
     * @param array $providers
     * @return Container
     * @throws FileException
     */
    protected function buildComponentProviderCache(array $providers): Container
    {
        $cache = new Container([
            'providers' => $providers, 'loaded' => [], 
            'framework' => [], 'application' => [], 
            'always' => [], 'deferred' => []
        ]);

        foreach($providers as $provider) {

            $instance = new $provider($this->app);

            // Register the providers as source.
            if($this->app->components()->isFrameworkType($provider)) {
                $cache->push('framework', $provider);

            } else {
                $cache->push('application', $provider);
            }

            // If the loader is deferred register as deferred.
            if ($instance->deferred()) {
                foreach($instance->components() as $component) {

                    // Save the components it loads.
                    $cache->add("deferred", [$component => $provider]);
                }

            } else {
                $cache->push("always", $provider);
            }
        }

        return $this->saveCache($cache);
    }

    /**
     * Save the cache to file.
     *
     * @param Container $cache
     * @return Container
     * @throws FileException
     */
    protected function saveCache(Container $cache): Container
    {
        (new FileBag($dirname = dirname($this->cache)))->put(
                str_replace($dirname, '', $this->cache),
                '<?php return '.var_export($cache->all(), true).';'
        );

        return $cache;
    }

    /**
     * Set the cache path.
     * 
     * @param string $path
     * @return void
     */
    public function setCachePath(string $path): void 
    {
        $this->cache = $path;
    }
}
