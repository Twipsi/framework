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

namespace Twipsi\Components\RateLimiter;

use Exception;
use Twipsi\Components\File\DirectoryManager;
use Twipsi\Components\File\FileBag as FileFactory;
use InvalidArgumentException;
use Twipsi\Support\Chronos;
use Twipsi\Support\Str;

class LimiterCache
{
    /**
     * File storage container.
     */
    protected FileFactory $files;

    /**
     * The cache file permission to set.
     * 
     * @var string
     */
    protected string $chmod = '0777';

    /**
     * Limiter cache constructor
     */
    public function __construct(protected string $cachePath) 
    {
        if (empty($cachePath)) {
            throw new InvalidArgumentException(
                "Please provide a path to cache limiters."
            );
        }

        // Build the main directory.
        $this->buildCacheDirectory($this->cachePath);

        // Load the file storage.
        $this->files = new FileFactory($this->cachePath);
    }

    /**
     * Create the cache file or replace the content.
     * 
     * @param string $key
     * @param int|string $value
     * @param int $seconds
     * 
     * @return void
     */
    public function put(string $key, int|string $value, int $seconds): void
    {
        $cacheFile = $this->getCacheFileName($key);

        $this->files->put($cacheFile, $this->getCacheFileValue($value, $seconds));

        $this->setCacheFilePermissions($cacheFile);
    }

    /**
     * Check if have a cache file.
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function has(string $key): bool 
    {
        return ! is_null($this->get($key));
    }

    /**
     * Get the cache file content or flush it its expired.
     * 
     * @param string $key
     * 
     * @return array|null
     */
    public function get(string $key): ?array
    {
        $file = $this->getCacheFileName($key);

        if(! $this->files->has($file)) {
            return null;
        }

        // Substract the expiration date form the cache.
        $expire = Str::hay($this->files->get($file))->pull(0, 10);

        // Delete it if it has expired.
        if(Chronos::date()->stamp() >= $expire){
            $this->deleteCacheFile($file);
            return null;
        }

        // Substract the content form the cache.
        try {
            $content = unserialize(Str::hay($this->files->get($file))->pull(10, null));

        } catch (Exception) {
            $this->deleteCacheFile($file);
            return null;
        }

        $remaining = $expire - Chronos::date()->stamp();

        return compact('content', 'remaining');
    }

    /**
     * Generate the cache filename.
     * 
     * @param string $key
     * 
     * @return string
     */
    public function getCacheFileName(string $key): string
    {
        $hash = sha1($key);

        return Str::hay($hash)->pull(0, 2).
            DIRECTORY_SEPARATOR.Str::hay($hash)->pull(2, 2).
            DIRECTORY_SEPARATOR.
            $hash;
    }

    /**
     * Generate the cache file content.
     * 
     * @param string $value
     * @param int $seconds
     * 
     * @return string
     */
    public function getCacheFileValue(int|string $value, int $seconds): string 
    {
        $date = Chronos::date()->addSeconds($seconds)->stamp();

        return $date.serialize($value);
    }

    /**
     * Increment the attempt count.
     * 
     * @param string $key
     * @param int $value
     * 
     * @return int|null
     */
    public function increment(string $key, $value = 1): ?int
    {
        if(! is_null($data = $this->get($key))) {

            $count = ((int)$data['content']) + $value;

            $this->put($key, $count, (int)$data['remaining']);

            return $count;
        }

        return null;
    }

    /**
     * Decrement the attempt count.
     * 
     * @param string $key
     * @param int $value
     * 
     * @return int|null
     */
    public function decrement(string $key, $value = 1): ?int 
    {
        return $this->increment($key, -1*abs($value));
    }

    /**
     * Delete a cache file.
     * 
     * @param string $key
     * 
     * @return void
     */
    public function deleteCacheFile(string $key): void
    {
        $this->files->delete($this->getCacheFileName($key));
    }

    /**
     * Flush all the files in the limiter cache directory.
     * @return void
     */
    public function flushLimiterCache(): void
    {
        $this->files->flush();
    }

    /**
     * Set the cache file permission.
     * 
     * @param string $file
     * 
     * @return void
     */
    protected function setCacheFilePermissions(string $file): void
    {
        if(is_null($this->chmod) || 
            intval($this->files->chmod($file), 8) == intval($this->chmod, 8)) {
                return;
        }

        $this->files->chmod($file, intval($this->chmod, 8));

    }

    /**
     * Build the cache directory if it isnt there.
     * 
     * @param string $path
     * 
     * @return void
     */
    protected function buildCacheDirectory(string $path): void
    {
        if (!is_dir($path)) {
            (new DirectoryManager())->make($path, 0777, true);
        }
    }
}
