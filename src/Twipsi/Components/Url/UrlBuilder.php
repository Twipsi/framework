<?php

namespace Twipsi\Components\Url;

use Twipsi\Support\Str;

final class UrlBuilder
{
    /**
     * The tokens holding the variadic parameters.
     */
    protected const PARAMETER_SELECTOR = "#\{(.*?)}#s";

    /**
     * The selector to identify inline parameter regex.
     */
    protected const REGEX_SELECTOR = ":";

    /**
     * The identifier for optional parameters.
     */
    protected const OPTIONAL_SELECTOR = "?";

    /**
     * The default value container.
     *
     * @var array
     */
    protected array $defaults = [];

    /**
     * Match the uri tokens against default parameters.
     *
     * @param string $uri
     * @param array $defaults
     * @param array $optionals
     * @return string
     */
    public function build(string $uri, array $defaults, array $optionals): string
    {
        $this->defaults = $defaults;

        // Replace all the identifiers with the corresponding values in the uri.
        $url = preg_replace_callback(self::PARAMETER_SELECTOR,
            function($matches) use ($optionals) {
                return $this->replaceParameterHolderWithValues($matches, $optionals);
            }, $uri);

        return $this->attachRemainingParameters(rtrim($url, '/'), $this->defaults);
    }

    /**
     * Replace the tokens with corresponding values.
     *
     * @param array $matches
     * @param array $optionals
     * @return string
     */
    protected function replaceParameterHolderWithValues(array $matches, array $optionals): string
    {
        $parameter = Str::hay($matches[1])->has(self::REGEX_SELECTOR)
            ? Str::hay($matches[1])->before(self::REGEX_SELECTOR)
            : $matches[1];

        if($this->isParameterOptional($parameter, $optionals)) {
            $parameter = Str::hay($parameter)->sliceStart('?');
        }

        if(array_key_exists($parameter, $this->defaults)) {
            $value = $this->defaults[$parameter];
            unset($this->defaults[$parameter]);
        }

        return $value ?? '';
    }

    /**
     * Check if a parameter is an optional parameter.
     *
     * @param string $parameter
     * @param array $optionals
     * @return bool
     */
    protected function isParameterOptional(string $parameter, array $optionals): bool
    {
        if(in_array($parameter, $optionals)) {
            return true;
        }

        return Str::hay($parameter)->first(self::OPTIONAL_SELECTOR);
    }

    /**
     * Attach the remaining values to the url.
     *
     * @param string $uri
     * @param array $parameters
     * @return string
     */
    protected function attachRemainingParameters(string $uri, array $parameters): string
    {
        foreach ($parameters as $name => $value) {
            $extra[] = $name . '=' . $value;
        }

        return !isset($extra) ? $uri : $uri . '?' . implode('&', $extra);
    }
}