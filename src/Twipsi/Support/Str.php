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

class Str
{
    /**
     * The string to manage.
     *
     * @var string
     */
    protected string $haystack;

    /**
     * Construct String.
     *
     * @param string $haystack
     */
    public function __construct(string $haystack)
    {
        $this->haystack = $haystack;
    }

    /**
     * Initialize Str object statically.
     *
     * @param string $string
     * @return Str
     */
    public static function hay(string $string): Str
    {
        return new self($string);
    }

    /**
     * Transform characters from one value to another.
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    public function convert(string $from, string $to): string
    {
        return strtr($this->haystack, $from, $to);
    }

    /**
     * Transform header names to lowercase.
     *
     * @return string
     */
    public function header(): string
    {
        return str_replace('_', '-', strtolower($this->haystack));
    }

    /**
     * Transform names to camelcase using a separator.
     *
     * @param string $separator
     * @return string
     */
    public function camelize(string $separator): string
    {
        return ucwords($this->haystack, $separator);
    }

    /**
     * Separate a string by uppercase letters and return them seperated.
     *
     * @return string
     */
    public function capitelize(): string
    {
        $capital = preg_replace_callback('/(?=[A-Z])/',
            function ($match) {
                return ucfirst($match[0]).' ';
            },
            $this->haystack
        );

        return ucfirst($capital);
    }

    /**
     * Convert a string to snake case.
     *
     * @return string
     */
    public function snake(): string
    {
        return preg_replace_callback('/([A-Z])+/',
            function ($match) {
                return strtolower('_'.$match[0]);
            },
            $this->haystack
        );
    }

    /**
     * Wrap a string between a character.
     *
     * @param string $wrapper
     * @return string
     */
    public function wrap(string $wrapper): string
    {
        return $wrapper.trim($this->haystack, $wrapper).$wrapper;
    }

    /**
     * Subtract content from a string based on positions.
     *
     * @param int|null $start
     * @param int|null $end
     * @param string $mode
     * @return string
     */
    public function pull(?int $start, ?int $end, string $mode = '8bit'): string
    {
        return mb_substr($this->haystack, $start, $end, $mode);
    }

    /**
     * Slice content of the start of a string.
     *
     * @param string $prefix
     * @return string
     */
    public function sliceStart(string $prefix): string
    {
        if (str_starts_with($this->haystack, $prefix)) {
            $this->haystack = substr($this->haystack, strlen($prefix));
        }

        return $this->haystack;
    }

    /**
     * Slice content of the end of a string.
     *
     * @param string $prefix
     * @return string
     */
    public function sliceEnd(string $prefix): string
    {
        if (str_ends_with($this->haystack, $prefix)) {
            $this->haystack = substr($this->haystack, 0, -strlen($prefix));
        }

        return $this->haystack;
    }

    /**
     * Transliterate a string.
     *
     * @return string
     */
    public function transliterate(): string
    {
        return Normalizer::transliterate($this->haystack);
    }

    /**
     * Get the first character in a string or compare it to a provided one.
     *
     * @param string|null $compare
     * @return string|bool
     */
    public function first(string $compare = null): string|bool
    {
        if (! is_null($compare)) {
            return $this->haystack[0] === $compare;
        }

        return $this->haystack[0] ?? '';
    }

    /**
     * Get the last character in a string or compare it to a provided one.
     *
     * @param string|null $compare
     * @return string|bool
     */
    public function last(string $compare = null): string|bool
    {
        if (! is_null($compare)) {
            return substr($this->haystack, -1) === $compare;
        }

        return substr($this->haystack, -1);
    }

    /**
     * Check if string is numeric.
     *
     * @return bool
     */
    public function numeric(): bool
    {
        return is_numeric($this->haystack);
    }

    /**
     * Check if string is alphanumeric.
     *
     * @return bool
     */
    public function alnum(): bool
    {
        return ctype_alnum($this->haystack);
    }

    /**
     * Get the index of a character in a string.
     *
     * @param string $value
     * @param bool $sensitive
     * @return int
     */
    public function index(string $value, bool $sensitive = false): int
    {
        if ($sensitive === false) {
            return !($index = stripos($this->haystack, $value)) ? -1 : $index;
        }

        return !($index = strpos($this->haystack, $value)) ? -1 : $index;
    }

    /**
     * Remove characters in a string.
     *
     * @param string ...$args
     * @return string
     */
    public function remove(string ...$args): string
    {
        return str_replace([...$args], '', $this->haystack);
    }

    /**
     * Replace needles with other needles.
     * 
     * @param string|array $what
     * @param array|string $with
     * @return string
     */
    public function replace(string|array $what, array|string $with): string
    {
        return str_replace($what, $with, $this->haystack);
    }

    /**
     * Check if string contains a needle.
     *
     * @param string $needle
     * @return bool
     */
    public function has(string $needle = ''): bool
    {
        return str_contains($this->haystack, $needle);
    }

    /**
     * Check if string contains any character in the list.
     *
     * @param string $list
     * @return bool
     */
    public function contains(string $list = ''): bool
    {
        return false !== strpbrk($this->haystack, $list);
    }

    /**
     * Check if string consists of a part of values.
     *
     * @param string ...$values
     * @return bool
     */
    public function resembles(string ...$values): bool
    {
        foreach (func_get_args() as $value) {
            $index = stripos($this->haystack, $value);

            if (false !== $index) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a string equals any values.
     *
     * @param ...$values
     * @return bool
     */
    public function is(...$values): bool
    {
        if (in_array($this->haystack, $values, true)) {
            return true;
        }

        return false;
    }

    /**
     * Slugify a string.
     *
     * @param string $separator
     * @return string
     */
    public function slugify(string $separator = '-'): string
    {
        return Normalizer::slugify($this->haystack, $separator);
    }

    /**
     * Retrieve string content after the first occurrence of a specific needle.
     *
     * @param string $needle
     * @return string|null
     */
    public function after(string $needle): ?string
    {
        if (! is_bool(strpos($this->haystack, $needle))) {
            return substr($this->haystack, strpos($this->haystack, $needle) + strlen($needle));
        }

        return null;
    }

    /**
     * Retrieve string content before the first occurrence of a specific needle.
     *
     * @param string $needle
     * @return string|null
     */
    public function before(string $needle): ?string
    {
        if (! is_bool(strpos($this->haystack, $needle))) {
            return substr($this->haystack, 0, strpos($this->haystack, $needle));
        }

        return null;
    }

    /**
     * Retrieve string content after the last occurrence of a specific needle.
     *
     * @param string $needle
     * @return string|null
     */
    public function afterLast(string $needle): ?string
    {
        if (! is_bool(strripos($this->haystack, $needle))) {
            return substr($this->haystack, strripos($this->haystack, $needle) + strlen($needle));
        }

        return null;
    }

    /**
     * Retrieve string content before the last occurrence of a specific needle.
     *
     * @param string $needle
     * @return string|null
     */
    public function beforeLast(string $needle): ?string
    {
        if (! is_bool(strripos($this->haystack, $needle))) {
            return substr($this->haystack, 0, strripos($this->haystack, $needle));
        }

        return null;
    }

    /**
     * Retrieve all the string content after a needle but before a needle.
     *
     * @param string $start
     * @param string $end
     * @return array|null
     */
    public function between(string $start, string $end): ?array
    {
        if (! is_bool(strpos($this->haystack, $start))
            && ! is_bool(strpos($this->haystack, $end))) {

            $startLength = strlen($start);
            $endLength = strlen($end);
            $startFrom = 0;

            while (false !== ($contentStart = strpos($this->haystack, $start, $startFrom))) {
                $contentStart += $startLength;
                $contentEnd = strpos($this->haystack, $end, $contentStart);

                if (! $contentEnd) {break;}

                $contents[] = substr($this->haystack, $contentStart, $contentEnd - $contentStart);
                $startFrom = $contentEnd + $endLength;
            }
        }

        return $contents ?? null;
    }

    /**
     * Retrieve first string content after a needle but before a needle.
     *
     * @param string $start
     * @param string $end
     * @return string|null
     */
    public function betweenFirst(string $start, string $end): ?string
    {
        if (! is_bool(strpos($this->haystack, $start)) && ! is_bool(strpos($this->haystack, $end))) {
            $this->haystack = $this->after($start);
            return $this->before($end);
        }

        return null;
    }

    /**
     * Retrieve last string content after a needle but before a needle.
     *
     * @param string $start
     * @param string $end
     * @return string|null
     */
    public function betweenLast(string $start, string $end): ?string
    {
        if (! is_bool(strripos($this->haystack, $start)) && ! is_bool(strripos($this->haystack, $end))) {
            $this->haystack = $this->beforeLast($end);
            return $this->afterLast($start);
        }

        return null;
    }
}
