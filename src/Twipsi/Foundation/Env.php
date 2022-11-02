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

class Env
{
    /**
     * Get an environment variable.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default): mixed 
    {
        $value = getenv($key) ?: ($_SERVER[$key] ?? null);

        if(is_numeric($value)) {
            $value = (int)$value;
        }
        elseif($value === 'true') {
            $value = true;
        }
        elseif($value === 'false') {
            $value = false;
        }

        return $value ?? $default;
    }
}