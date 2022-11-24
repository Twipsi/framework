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

use ReflectionException;
use Twipsi\Components\File\Exceptions\DirectoryManagerException;
use Twipsi\Components\File\Exceptions\FileException;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileBag;
use Twipsi\Components\File\FileItem;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Env;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
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
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The list of component providers.
     *
     * @var array|null
     */
    protected array|null $providers;

    /**
     * Construct Bootstrapper.
     *
     * @param Application $app
     * @throws ReflectionException
     * @throws ApplicationManagerException
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->providers = $this->app->get('config')
            ->get('component.loaders')?->all();

        $this->cache = $this->app->componentCacheFile();
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     * @throws FileException|DirectoryManagerException
     */
    public function invoke(): void
    {
        if(empty($this->providers)) {
            return;
        }

        $this->load($this->providers);

        // Flag the components as registered in the application.
        $this->app->components()->setRegistered();
    }

    /**
     * Load all the component providers.
     *
     * @param array $providers
     * @return void
     * @throws FileException|DirectoryManagerException
     */
    public function load(array $providers): void
    {
        if(Env::get('CACHE_COMPONENTS', 'false')) {

            $cache = $this->getComponentProviderCache();

            // If we need to rebuild the cache then rebuild it.
            if($this->shouldRebuildCache($cache, $providers)) {
                $repository = $this->buildComponentProviderRepository($providers);
                $this->saveCache($repository);
            }

            $repository = ! isset($repository) ? new Container($cache) : $repository;
        }

        // Build the repository in case we are not using cache.
        $repository = $repository ?? $this->buildComponentProviderRepository($providers);

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
            $file = new FileItem($this->cache);

            return $file->include();

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
     * Save the cache to file.
     *
     * @param Container $cache
     * @return void
     * @throws FileException|DirectoryManagerException
     */
    protected function saveCache(Container $cache): void
    {
        (new FileBag($dirname = dirname($this->cache)))->put(
            str_replace($dirname, '', $this->cache),
            '<?php return '.var_export($cache->all(), true).';'
        );
    }
}
