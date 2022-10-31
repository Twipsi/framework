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

namespace Twipsi\Components\Translator;

use Twipsi\Components\User\Interfaces\ILocalizable as Localizable;
use Twipsi\Support\Arr;
use Twipsi\Support\Bags\ArrayBag as Container;

class Translator
{
    use HandlesUser;

    /**
     * The current locale being used.
     * 
     * @var string
     */
    protected string $locale;

    /**
     * The container holding the loaded translations.
     * 
     * @var Container
     */
    protected Container $translations;

    /**
     * Translator constructor
     */
    public function __construct(protected LocaleLocator $locator, string $locale) 
    {
        $this->setLocale($locale);
        $this->translations = new Container;
    }

    /**
     * Find and load group translation for the locale.
     * 
     * @param string $locale
     * @param string $group
     * 
     * @return void
     */
    public function load(string $locale, string $group): void 
    {
        if($this->translations->has($locale.'.'.$group)) {
            return;
        }

        if($file = $this->locator->locate($locale, $group)) {
            $this->translations->set($locale.'.'.$group, $file->extractContent());
        }
    }

    /**
     * Get the translation for a specific line or group.
     * 
     * @param string $key
     * @param array $tokens
     * @param string|null $locale
     * 
     * @return string
     */
    public function get(string $key, array $tokens = [], string $locale = null): null|string|array
    {
        [$group, $entry] = $this->parseKey($key);
        $locale = $locale ?: $this->getLocale();

        // Load the translation file with the translation data.
        $this->load($locale ?: $this->getLocale(), $group);

        if(! is_null($line = $this->getLine($locale, $group, $entry, $tokens))) {
            return $line;
        }

        // If we did not find an entry in the locale 
        // then return the text while replacing tokens.
        return $this->replaceTokens($key, $tokens);
    }

    /**
     * Get the requested line from the translation data.
     * 
     * @param string $locale
     * @param string $group
     * @param string $key
     * @param array $tokens
     * 
     * @return null|string|array 
     */
    protected function getLine(string $locale, string $group, string $key, array $tokens): null|string|array 
    {
        $key = trim($locale.'.'.$group.'.'.$key,'.');

        $line = $this->translations->get($key);

        if(is_string($line)) {
            return $this->replaceTokens($line, $tokens);
        }

        if(is_array($line) && !empty($line)) {

            array_walk_recursive($line, 
                function(&$value) use ($tokens) { 
                    $value = $this->replaceTokens($value, $tokens);
            });

            return $line;
        }

        return null;
    }

    /**
     * Replace all the tokens set with ":" from the tokens container.
     * 
     * @param string $line
     * @param array $tokens
     * 
     * @return string|null
     */
    protected function replaceTokens(string $line, array $tokens): ?string 
    {
        return preg_replace_callback(
            "/\B:(\w+)/x",
            function ($match) use($tokens) {
                if(isset($tokens[$match[1]]) && is_array($tokens[$match[1]])) {
                    return implode(', ', $tokens[$match[1]]);
                }

                return isset($tokens[$match[1]]) ? $tokens[$match[1]] : $match[1];
            },
            $line
        );
    }

    /**
     * Parse the key data to find the line.
     * 
     * @param string $key
     * 
     * @return array
     */
    protected function parseKey(string $key): array 
    {
        $segments = explode('.', $key);

        return [array_shift($segments), implode('.', $segments)];
    }

    /**
     * Get the current locale being used.
     * 
     * @return string
     */
    public function getLocale(): string 
    {
        // If a user has a prefered language saved.
        $user = $this->loadUser();

        if($user && $user instanceof Localizable) {
            return $user->getLocale() ?? $this->locale;
        }

        return $this->locale;
    }

    /**
     * Set the current locale to use.
     * 
     * @param string $locale
     * 
     * @return void
     */
    public function setLocale(string $locale): void 
    {
        $this->locale = str_replace(['\\', '/'], '', $locale);
    }
}