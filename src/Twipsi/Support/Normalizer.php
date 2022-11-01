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

class Normalizer
{
    /**
     * UTF8 Converts.
     */
    protected const UTF8 = [
        '/[áàâãªä]/u' => 'a',
        '/[ÁÀÂÃÄ]/u' => 'A',
        '/[ÍÌÎÏ]/u' => 'I',
        '/[íìîï]/u' => 'i',
        '/[éèêë]/u' => 'e',
        '/[ÉÈÊË]/u' => 'E',
        '/[óòôõºöő]/u' => 'o',
        '/[ÓÒÔÕÖŐ]/u' => 'O',
        '/[úùûüű]/u' => 'u',
        '/[ÚÙÛÜŰ]/u' => 'U',
        '/ç/' => 'c',
        '/Ç/' => 'C',
        '/ñ/' => 'n',
        '/Ñ/' => 'N',
        '/–/' => '-',
        '/[’‘‹›‚]/u' => ' ',
        '/[“”«»„]/u' => ' ',
        '/ /' => ' ',
    ];

    /**
     * Pattern to use when slugifying.
     */
    protected const SLUG_PATTERN = "/[^0-9a-zA-Z%s]+/";

    /**
     * Exception characters when slugifying,
     * that should be replaced by the separator.
     */
    protected const BREAKERS = '\\/_|+ -';

    /**
     * Exception characters when slugifying an url,
     * that should not be replaced.
     */
    protected const GUARDED = '\\/?&:=.';

    /**
     * Normalize a string while just removing lines and spaces,
     * or normalizing the encoding if set to strict.
     *
     * @param string $string
     * @param bool $transliterate
     * @return string
     */
    public static function normalizeString(string $string, bool $transliterate = false): string
    {
        // Strip all the html tags if any.
        $string = self::stripTags($string);

        // Transliterate the string.
        $string = $transliterate ? self::transliterate($string) : $string;

        return self::stripLines($string);
    }

    /**
     * Slugify a path or uri.
     *
     * @param string $uri
     * @param string $separator
     * @return string
     */
    public static function slugifyPath(string $uri, string $separator = '-'): string
    {
        // Decode, strip, lower and remove the right slash.
        $uri = rtrim(strtolower(strip_tags(urldecode($uri))), '/');

        // Do a basic clean of the string.
        $uri = self::normalizeString(
            str_replace(['\\'], '/', trim($uri)), true
        );

        // Execute the pattern matching.
        $uri = preg_replace(
            sprintf(self::SLUG_PATTERN, self::GUARDED), "", strtolower($uri)
        );

        // Replace separators with seperator.
        $uri = preg_replace(
            sprintf("/[%s]+/", str_replace('\\/', '', self::BREAKERS)), $separator, $uri
        );

        return trim($uri, $separator);
    }

    /**
     * Slugify a string.
     *
     * @param string $string
     * @param string $separator
     * @return string
     */
    public static function slugifyString(string $string, string $separator = '-'): string
    {
        // Do a basic clean of the string.
        $string = self::normalizeString($string, true);

        // Execute the pattern matching.
        $string = preg_replace(
            sprintf(self::SLUG_PATTERN, self::BREAKERS), "", strtolower($string)
        );

        // Replace separators with seperator.
        $string = preg_replace(sprintf("/[%s]+/", self::BREAKERS), $separator, $string);

        return trim($string, $separator);
    }

    /**
     * Slugify a string.
     *
     * @param string $string
     * @param string $separator
     * @return string
     */
    public static function slugify(string $string, string $separator = '-'): string
    {
        return static::slugifyString($string, $separator);
    }

    /**
     * Strip any lines or tabs on the string.
     * 
     * @param string $string
     * @return string
     */
    public static function stripLines(string $string): string
    {
        // Remove any tab spaces.
        $string = preg_replace("/\t/", "", $string);

        // Remove new lines and breaks.
        return preg_replace("/\r?\n/", "", $string);
    }

    /**
     * Decode and strip all html tags.
     * 
     * @param string $string
     * @return string
     */
    public static function stripTags(string $string): string
    {
        // Decode any possible html entities.
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

        // Strip all the html tags if any.
        return trim(strip_tags($string));
    }

    /**
     * Normalize encoding to UTF8
     *
     * @param string $string
     * @return string
     */
    public static function transliterate(string $string): string
    {
        return function_exists('transliterator_transliterate')
          ? self::transliterateWithTransliterator($string)
          : preg_replace(array_keys(self::UTF8), array_values(self::UTF8), $string);
    }

    /**
     * Transliterate a string.
     *
     * @param string $string
     * @return string
     */
    protected static function transliterateWithTransliterator(string $string): string
    {
        return transliterator_transliterate(
            'NFD; NFC; Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove;', $string
        );
    }
}

