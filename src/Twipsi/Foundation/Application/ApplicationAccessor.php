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

use Twipsi\Foundation\Exceptions\ApplicationManagerException;

trait ApplicationAccessor
{
    /**
     * Send direct get request through call method.
     *
     * @param string $instance
     * @return mixed
     * @throws ApplicationManagerException
     */
    public function __get(string $instance): mixed
    {
        if (method_exists($this, $instance)) {
            return $this->{$instance};
        }

        return $this->make($instance);
    }

    /**
     * Get instance with get method.
     *
     * @param $instance
     * @return mixed
     * @throws ApplicationManagerException
     */
    public function get($instance): mixed
    {
        return $this->make($instance);
    }

    /**
     * Check if offset exists.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->instances->has($key);
    }

    /**
     * Get offset value.
     *
     * @param mixed $key
     * @return mixed
     * @throws ApplicationManagerException
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->make($key);
    }

    /**
     * Set offset value.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->instances->set($key, $value);
    }

    /**
     * Delete offset.
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void
    {
        $this->instances->delete($key);
    }
}
