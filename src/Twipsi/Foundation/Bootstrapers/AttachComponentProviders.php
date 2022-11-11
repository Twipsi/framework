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
use Twipsi\Foundation\Env;
use Twipsi\Support\Bags\SimpleBag as Container;

class AttachComponentProviders
{
    use ResolveComponentProviders;

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
        $providers = $this->app->get('config')
            ->get('component.loaders')->all();

        if(empty($providers)) {
            return;
        }

        $this->setCachePath($this->app->componentCacheFile());
        $this->load($providers);

        // Flag the components as registered in the application.
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
            $repository = $this->buildComponentProviderCache($providers);

            // Save the cache file.
            $this->saveCache($repository);
        } else if(! Env::get('CACHE_COMPONENTS', false)) {
            $repository = $this->buildComponentProviderCache($providers);
        }

        $repository = ! isset($repository)
            ? new Container($cache)
            : $repository;

        // Merge the cache data into the registry.
        $this->app->components()->inject($repository->all());

        // Run through the cache and load providers.
        foreach($repository->get('always') as $provider) {
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
        if(Env::get('CACHE_COMPONENTS', false)) {
            return empty($cache) || $cache['providers'] != $providers;
        }

        return false;
    }

    /**
     * Save the cache to file.
     *
     * @param Container $cache
     * @return void
     * @throws FileException
     */
    protected function saveCache(Container $cache): void
    {
        (new FileBag($dirname = dirname($this->cache)))->put(
                str_replace($dirname, '', $this->cache),
                '<?php return '.var_export($cache->all(), true).';'
        );
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
