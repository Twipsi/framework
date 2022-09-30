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

namespace Twipsi\Support\Bags;

use ArrayAccess;

class ArrayAccessibleBag extends RecursiveArrayBag implements ArrayAccess
{
    /**
     * Check if offset exists.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->has($key);
    }

    /**
     * Get offset value.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }


    /**
     * Set offset value.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Delete offset.
     *
     * @param mixed $key
     *
     * @return void
     */
    public function offsetUnset(mixed $key): void
    {
        $this->delete($key);
    }
}
