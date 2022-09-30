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

use Twipsi\Components\RateLimiter\LimiterCache;
use Twipsi\Support\Chronos;

class RateLimiter
{
    protected const TIMER_TOKEN = ':timer';

    public function __construct(protected LimiterCache $cache){}

    /**
     * Check if we have reached the attempt limit.
     * 
     * @param string $key
     * @param int $maxAttempts
     * 
     * @return bool
     */
    public function isOverTheLimit(string $key, int $maxAttempts): bool
    {
        // First check if the attempts are valid and below max.
        if($this->getAttemptsMade($key) >= $maxAttempts) {

            // Check if the timer file is valid.
            if($this->cache->has($this->cleanKeyFromUnicodeCharacters($key).self::TIMER_TOKEN)) {
                return true;
            }

            // Reset the attempt number if the timer has expired.
            $this->resetAttemptsMade($key);
        }


        return false;
    }

    /**
     * Make an attempt and store in cache.
     * 
     * @param string $key
     * @param int $decayInSeconds
     * 
     * @return int
     */
    public function makeAttempt(string $key, int $decayInSeconds = 60): int
    {
        $key = $this->cleanKeyFromUnicodeCharacters($key);

        // Add the cache file containing the timer.
        $this->cache->put(
            $key.self::TIMER_TOKEN,
            Chronos::date()->addSeconds($decayInSeconds)->stamp(),
            $decayInSeconds
        );

        // Add the cache file containing the attempt count.
        if(! $this->cache->has($key)) {
            $this->cache->put($key, 0, $decayInSeconds);
        }
        
        // Incremement the attempt number.
        return $this->cache->increment($key) ?? 0;
    }

    /**
     * Get the attempt count made.
     * 
     * @param string $key
     * 
     * @return int
     */
    public function getAttemptsMade(string $key): int 
    {
        if(! is_null($data = $this->cache->get($this->cleanKeyFromUnicodeCharacters($key)))) {
            return $data['content'];
        }

        return 0;
    }

    /**
     * Get the remaining attempts left.
     * 
     * @param string $key
     * @param int $maxAttempts
     * 
     * @return int
     */
    public function getRemainingAttempts(string $key, int $maxAttempts): int 
    {
        return $maxAttempts - $this->getAttemptsMade($key);
    }

    /**
     * Get the remaining time before we can attempt again.
     * 
     * @param string $key
     * 
     * @return int
     */
    public function getLockoutTimer(string $key): int 
    {
        $key = $this->cleanKeyFromUnicodeCharacters($key);

        return max(0, ($this->cache->get($key.self::TIMER_TOKEN)['content'] ?? 0) - Chronos::date()->stamp());
    }

    /**
     * Reset the attempt count in the cache file.
     * 
     * @param string $key
     * 
     * @return void
     */
    public function resetAttemptsMade(string $key): void 
    {
       $this->cache->deleteCacheFile($this->cleanKeyFromUnicodeCharacters($key));
    }

    /**
     * Clear the attempts and the lockout.
     * 
     * @param string $key
     * 
     * @return void
     */
    public function clearLimiter(string $key): void 
    {
        $this->resetAttemptsMade($key);
        $this->cache->deleteCacheFile($this->cleanKeyFromUnicodeCharacters($key).self::TIMER_TOKEN);
    }

    /**
     * Clean the cache file name from unicode.
     * 
     * @param string $key
     * 
     * @return string
     */
    public function cleanKeyFromUnicodeCharacters(string $key): string
    {
        return preg_replace('/&([a-z])[a-z]+;/i', '$1', htmlentities($key));
    }
}