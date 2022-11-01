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

class Jso
{
    /**
     * The JSON data.
     *
     * @var mixed
     */
    protected mixed $haystack;

    /**
     * Construct JSO.
     *
     * @param mixed $haystack
     */
    public function __construct(mixed $haystack)
    {
        $this->haystack = $haystack;
    }

    /**
     * Set the data we are working on.
     *
     * @param mixed $data
     * @return Jso
     */
    public static function hay(mixed $data): Jso
    {
        return new self($data);
    }

    /**
     * Encode data to json.
     *
     * @param int $flags
     * @return string
     */
    public function encode(int $flags = 0): string
    {
        return json_encode($this->haystack, $flags);
    }

    /**
     * Decode Json data.
     *
     * @param bool|null $associative
     * @return mixed
     */
    public function decode(?bool $associative = null): mixed
    {
        return json_decode($this->haystack, $associative);
    }

    /**
     * Check if data is a valid json.
     *
     * @return bool
     */
    public function json(): bool
    {
        if (is_array($this->haystack) || is_null($this->haystack)) {
            return false;
        }

        return isset($this->haystack[0]) && $this->haystack[0] === '{';
    }

    /**
     * Get last json error.
     *
     * @return int
     */
    public static function error(): int
    {
        return json_last_error();
    }

    /**
     * Check if json conversion was valid.
     *
     * @return bool
     */
    public static function valid(): bool
    {
        return json_last_error() === JSON_ERROR_NONE;
    }
}
