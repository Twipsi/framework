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

namespace Twipsi\Support;

use Twipsi\Support\Str;

class KeyGenerator
{
    /**
     * Generate a random secure key.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateSecureKey(int $length = 32): string
    {
        return substr(static::baseEncode(random_bytes($length)), 0, $length);
    }

    /**
     * Generate an alphanumeric key lowercased.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateAlphanumeric(int $length = 32): string
    {
        return bin2hex(random_bytes($length/2));
    }

    /**
     * Generate a varbinary key.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateByteKey(int $length = 32): string
    {
        return random_bytes($length);
    }

    /**
     * Generate a universaly unique identifier key.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateUUIDKey(int $length = 32): string
    {
        $key = self::generateByteKey($length);
        assert(strlen($key) == $length);

        // Set version to 0100
        $key[6] = chr(ord($key[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $key[8] = chr(ord($key[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($key), 4));
    }

    /**
     * Generate a complex system key.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateSystemKey(int $length = 64): string
    {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%&'()*+,-.:;<=>?@[]^_`{|}~";
        $maximum = strlen($characters);
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[rand(0, $maximum-1)];
        }

        return $key;
    }

    /**
     * Base encode string stripping special characters.
     *
     * @param string $string
     *
     * @return string
     */
    public static function baseEncode(string $string): string
    {
        return str_replace(['=', '+', '/'], '', base64_encode($string));
    }
}
