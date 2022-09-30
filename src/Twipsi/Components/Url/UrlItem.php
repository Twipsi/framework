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

namespace Twipsi\Components\Url;

use InvalidArgumentException;
use Stringable;
use Twipsi\Components\Http\Exceptions\MalformedUrlException;

use const PHP_QUERY_RFC3986;

final class UrlItem implements Stringable
{
    private const CREDENTIAL_SEPARATOR = '@';
    private const FRAGMENT_SEPARATOR = '#';
    private const USERINFO_SEPARATOR = ':';
    private const PORT_SEPARATOR = ':';
    private const SCHEME_SEPARATOR = '://';

    /**
     * Original url string.
     *
     * @var string|null
     */
    protected string|null $originalUrl;

    /**
     * Parsed url parts.
     *
     * @var array
     */
    protected array $parsedUrlParts = [];

    /**
     * Parsed url params.
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Construct Url object.
     *
     * @param string|null $url
     * @throws MalformedUrlException
     */
    public function __construct(?string $url)
    {
        $this->originalUrl = $url;
        $this->buildUrl($url);
    }

    /**
     * Parse and build the url object.
     *
     * @param string|null $url
     * @return void
     * @throws MalformedUrlException
     */
    protected function buildUrl(?string $url): void
    {
        if (!$url || empty(rtrim($url, '/'))) {
            return;
        }

        // Parse the url for parts.
        $this->parsedUrlParts = $this->parseUrl(trim($url));

        // If we have a query parse the parameters.
        if (isset($this->parsedUrlParts['query'])) {
            $this->params = $this->parseQueryString($this->parsedUrlParts['query']);
        }
    }

    /**
     * Parse the provided url.
     *
     * @param string $url
     * @return array
     * @throws MalformedUrlException
     */
    public function parseUrl(string $url): array
    {
        $encoded = preg_replace_callback('/[^:\/@?&=#]+/u',
            static function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );

        if (empty($parts = parse_url($encoded))) {
            throw new MalformedUrlException(sprintf('Failed to parse url [%s]', $url));
        }

        return array_map('urldecode', $parts);
    }

    /**
     * Parse and set query string.
     * ex. name=ferret&color=purple.
     *
     * @param string $query
     * @return array
     */
    public function parseQueryString(string $query): array
    {
        $params = [];
        parse_str($query, $params);

        return $params;
    }

    /**
     * Return the original url.
     *
     * @return string|null
     */
    public function original(): ?string
    {
        return $this->originalUrl;
    }

    /**
     * Check if url is relative.
     *
     * @return bool
     */
    public function isRelative(): bool
    {
        return is_null($this->getHost());
    }

    /**
     * Get the host of the url.
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->parsedUrlParts['host'] ?? null;
    }

    /**
     * Set the scheme of the url.
     *
     * @param string $scheme
     * @return $this
     */
    public function setScheme(string $scheme): UrlItem
    {
        if (!in_array($scheme, ['https', 'http'])) {
            throw new InvalidArgumentException(sprintf('Requested scheme is not valid: "%s"', $scheme));
        }

        $this->parsedUrlParts['scheme'] = $scheme;

        return $this;
    }

    /**
     * Set the host of the url.
     *
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): UrlItem
    {
        $this->parsedUrlParts['host'] = $host;

        return $this;
    }

    /**
     * Set the port of the url.
     *
     * @param int $port
     * @return $this
     */
    public function setPort(int $port): UrlItem
    {
        $this->parsedUrlParts['port'] = $port;

        return $this;
    }

    /**
     * Set the username for the url.
     *
     * @param string $username
     * @return $this
     */
    public function setUser(string $username): UrlItem
    {
        $this->parsedUrlParts['username'] = $username;

        return $this;
    }

    /**
     * Set the password for the url.
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): UrlItem
    {
        $this->parsedUrlParts['password'] = $password;

        return $this;
    }

    /**
     * Get the parameters of the url.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get the fragment of the url.
     *
     * @return string|null
     */
    public function getFragment(): ?string
    {
        return $this->parsedUrlParts['fragment'] ?? null;
    }

    /**
     * Get the scheme of the url.
     *
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->parsedUrlParts['scheme'] ?? null;
    }

    /**
     * Get the username from the url.
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->parsedUrlParts['username'] ?? null;
    }

    /**
     * Get the password from the url.
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->parsedUrlParts['password'] ?? null;
    }

    /**
     * Get the scheme with host and port formatted.
     *
     * @return string
     */
    public function getSchemeWithHost(): string
    {
        return $this->getScheme() . self::SCHEME_SEPARATOR . $this->getHostWithPort();
    }

    /**
     * Get the host with port number if any.
     *
     * @return string
     */
    public function getHostWithPort(): string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        $host = $this->getHost();

        if (('http' == $scheme && 80 == $port) || ('https' == $scheme && 443 == $port)) {
            return $host;
        }

        if (!is_null($port)) {
            $host = $host . self::PORT_SEPARATOR . $port;
        }

        return $host ?? '';
    }

    /**
     * Get the port of the url.
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return isset($this->parsedUrlParts['port'])
            ? (int)$this->parsedUrlParts['port'] : null;
    }

    /**
     * Get the username and password of the url formatted.
     *
     * @return string
     */
    public function getUserCredentials(): string
    {
        $credentials = $this->getUser();

        if (!empty($pass = $this->getPassword())) {
            $credentials .= self::USERINFO_SEPARATOR . $pass;
        }

        return $credentials ?? '';
    }

    /**
     * Retrieve normalized relative root that
     * removes trailing slash at the end.
     *
     * @return string
     */
    public function getRelativeRoot(): string
    {
        return rtrim($this->getBaseUrl(), '/' . DIRECTORY_SEPARATOR);
    }

    /**
     * Retrieve the root URL ( root before the executed script )
     * that never ends with '/'.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        $filename = basename($_SERVER['SCRIPT_FILENAME']);
        $requestUri = $this->getRequestUri();

        $baseUrl = ($pos = strpos($requestUri, $filename))
            ? substr($requestUri, 0, $pos) : '';

        return rtrim($baseUrl, '/' . DIRECTORY_SEPARATOR);
    }

    /**
     * Get the URI of the request ( path and query string ).
     *
     * @param bool $strict
     * @return string
     */
    public function getRequestUri(bool $strict = false): string
    {
        $queryString = $this->getQueryString($strict);
        $queryString = !is_null($queryString) ? '?' . $queryString : '';

        return ($this->parsedUrlParts['path'] ?? '/') . $queryString;
    }

    /**
     * Retrieve query string as rebuilt string.
     * ex. name=ferret&color=purple.
     *
     * @param bool $strict
     * @return string|null
     */
    public function getQueryString(bool $strict = false): ?string
    {
        if (empty($this->params)) {
            return null;
        }

        if ($strict) {
            $params = array_filter($this->params, function ($k) {
                return (trim($k) !== '');
            });
        }

        return http_build_query(
            $params ?? $this->params, '', '&', PHP_QUERY_RFC3986
        );
    }

    /**
     * Retrieve normalized absolute request url that
     * removes trailing slash at the end.
     *
     * @param bool $query
     * @param bool $strict
     * @return string
     */
    public function getAbsoluteUrl(bool $query = true, bool $strict = false): string
    {
        $credentials = $this->getUserCredentials();
        $credentials = !empty($credentials) ? $credentials . self::CREDENTIAL_SEPARATOR : '';

        return $this->getScheme() . self::SCHEME_SEPARATOR . $credentials . $this->getHostWithPort() . $this->getRelativeUrl($query, $strict);
    }

    /**
     * Retrieve normalized relative request url that
     * removes trailing slash at the end.
     *
     * @param bool $query
     * @param bool $strict
     * @return string
     */
    public function getRelativeUrl(bool $query = true, bool $strict = false): string
    {
        $fragment = isset($this->parsedUrlParts['fragment'])
            ? self::FRAGMENT_SEPARATOR . $this->parsedUrlParts['fragment'] : '';

        if ($query && !is_null($querystring = $this->getQueryString($strict))) {

            return rtrim(
                $this->getBaseUrl() . $this->getPath() . '?' . $querystring . $fragment,
                '/' . DIRECTORY_SEPARATOR
            );
        }

        $uri = $this->getBaseUrl() . $this->getPath();
        return 1 < strlen($uri) ? rtrim($uri, '/' . DIRECTORY_SEPARATOR) : $uri;
    }

    /**
     * Get the path of the url that should start with '/' always.
     *
     * @return string
     */
    public function getPath(): string
    {
        $path = $this->parsedUrlParts['path'] ?? '';

        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }

        return !empty($path) ? $path : '/';
    }

    /**
     * Return string url if called as string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAbsoluteUrl();
    }
}
