<?php

/*
* This file is part of the Twipsi package.
*
* (c) Petrik Gábor <twipsi@twipsi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Twipsi\Foundation;

final class Env
{
    /**
     * Get an environment variable.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key) ?: ($_SERVER[$key] ?? null);

        return match ($value) {
            is_numeric($value) => (int)$value,
            $value === 'true' => true,
            $value === 'false' => false,
            empty($value) => $default,
            default => $value ?? $default,
        };
    }

    /**
     * Get all the environment variables.
     *
     * @return array
     */
    public static function all(): array
    {
        return getenv();
    }
}