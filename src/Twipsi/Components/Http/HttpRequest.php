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

namespace Twipsi\Components\Http;

use Twipsi\Components\Url\UrlItem;
use Twipsi\Support\Bags\ArrayBag;
use Twipsi\Components\Http\Exceptions\MaliciousRequestException;
use InvalidArgumentException;
use Twipsi\Components\Http\Exceptions\NotSupportedException;

class HttpRequest
{
    use HandlesInput;
    use HandlesSession;
    use HandlesCookies;
    use HandlesUser;
    use HandlesValidation;

    public const REQUEST_METHOD_HEAD      = 'HEAD';
    public const REQUEST_METHOD_GET       = 'GET';
    public const REQUEST_METHOD_POST      = 'POST';
    public const REQUEST_METHOD_PUT       = 'PUT';
    public const REQUEST_METHOD_PATCH     = 'PATCH';
    public const REQUEST_METHOD_DELETE    = 'DELETE';
    public const REQUEST_METHOD_PURGE     = 'PURGE';
    public const REQUEST_METHOD_OPTIONS   = 'OPTIONS';
    public const REQUEST_METHOD_TRACE     = 'TRACE';
    public const REQUEST_METHOD_CONNECT   = 'CONNECT';

    public const CONTENT_TYPE_HTML            = ['text/html', 'application/xhtml+xml'];
    public const CONTENT_TYPE_TXT             = ['text/plain'];
    public const CONTENT_TYPE_CSS             = ['text/css'];
    public const CONTENT_TYPE_JS              = ['application/javascript', 'application/x-javascript', 'text/javascript'];
    public const CONTENT_TYPE_JSON            = ['application/json', 'application/x-json'];
    public const CONTENT_TYPE_XML             = ['text/xml', 'application/xml', 'application/x-xml'];
    public const CONTENT_TYPE_FORM_DATA       = ['multipart/form-data'];
    public const CONTENT_TYPE_X_FORM_ENCODED  = ['application/x-www-form-urlencoded'];

    public const FORCE_METHOD_OVERRIDE_KEY    = '_method';
    public const DEFAULT_SYSTEM_LOCALE        = 'en-US';
    public const HTTPS_PORT                   = 443;
    public const HTTP_PORT                    = 80;

    /**
    * Contains supported request methods
    */
    private static array $supportedMethods = [
      self::REQUEST_METHOD_HEAD,
      self::REQUEST_METHOD_GET,
      self::REQUEST_METHOD_POST,
      self::REQUEST_METHOD_PUT,
      self::REQUEST_METHOD_PATCH,
      self::REQUEST_METHOD_DELETE,
      self::REQUEST_METHOD_PURGE,
      self::REQUEST_METHOD_OPTIONS,
      self::REQUEST_METHOD_TRACE,
      self::REQUEST_METHOD_CONNECT,
    ];

    /**
    * Contains supported formats.
    */
    private static array $supportedFormats = [
      'HTML' => self::CONTENT_TYPE_HTML,
      'TXT' => self::CONTENT_TYPE_TXT,
      'CSS' => self::CONTENT_TYPE_CSS,
      'JS' => self::CONTENT_TYPE_JS,
      'JSON' => self::CONTENT_TYPE_JSON,
      'XML' => self::CONTENT_TYPE_XML,
      'FORM_DATA' => self::CONTENT_TYPE_FORM_DATA,
      'X_FORM_ENCODED' => self::CONTENT_TYPE_X_FORM_ENCODED,
    ];

    /**
    * Contains safe type methods.
    */
    private static array $requestTypeSafe = [
      self::REQUEST_METHOD_HEAD,
      self::REQUEST_METHOD_GET,
      self::REQUEST_METHOD_OPTIONS,
      self::REQUEST_METHOD_DELETE,
      self::REQUEST_METHOD_TRACE,
    ];

    /**
    * Contains data type methods.
    */
    private static array $requestTypeData = [
      self::REQUEST_METHOD_POST,
      self::REQUEST_METHOD_PUT,
      self::REQUEST_METHOD_PATCH,
      self::REQUEST_METHOD_DELETE,
    ];

    /**
    * Contains read type methods.
    */
    private static array $readTypeData = [
      self::REQUEST_METHOD_GET,
      self::REQUEST_METHOD_HEAD,
      self::REQUEST_METHOD_OPTIONS
    ];

    /**
    * Additional request data.
    */
    public ArrayBag $properties;

    /**
    * $_SERVER data.
    */
    public HeaderBag $headers;

    /**
    * URL data.
    */
    public UrlItem $url;

    /**
    * Http Request method.
    */
    protected string|null $method = null;

    /**
    * System locale
    */
    protected string|null $locale = null;

    /**
    * Accepted content types.
    */
    protected array|null $acceptedContentTypes = null;

    /**
    * Accepted encodings.
    */
    protected array|null $acceptedEncodings = null;

    /**
    * Accepted charsets
    */
    protected array|null $acceptedCharsets = null;

    /**
    * Accepted languages.
    */
    protected array|null $acceptedLanguages = null;

    /**
    * Content of the request.
    */
    protected string|null $requestContent = null;

    /**
     * Construct a new HttpRequest.
     *
     * @param array|null $headers
     * @param array|null $get
     * @param array|null $post
     * @param array|null $files
     * @param array|null $cookies
     * @param array $properties
     */
    public function __construct(array $headers = [], array $get = [], array $post = [], array $files = [], array $cookies = [], array $properties = [])
    {
        $this->properties  = new ArrayBag($properties);
        $this->headers  = new HeaderBag($headers);
        $this->setRequestData($this, $post);
        $this->setQueryData($this, $get);
        $this->setFileData($files);
        $this->setCookies($cookies);

        if ($this->isJson()) {
            $this->json = new InputBag($this, (array) json_decode($this->getContent(), true));
        }

        $this->setUrl(new UrlItem($this->headers->find('unencoded-url', 'request-uri')));

        return $this;
    }

    /**
    * Retrieve the host name.
    *
    * @throws MaliciousRequestException
    */
    public function getHost(): string
    {
        // Get parsed host from Url object in priority.
        if ($host = $this->url->getHost()) {
            $host = strtolower(trim($host));
        }

        // Get host from header bag
        if (! $host && ! $host = $this->headers->get('http-host')) {
            $host = $this->headers->get('server-addr') ?? '';

            // Remove port.
            $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
        }

        // Check that it does not contain forbidden characters (see RFC 952 and RFC 2181).
        if ($host && preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host) !== '') {
            throw new MaliciousRequestException(sprintf('Requested host is invalid: [%s]', $host));
        }

        return $host;
    }

    /**
    * Set the request method
    *
    * @throws NotSupportedException
    */
    public function setMethod(string $method): void
    {
        if (in_array(strtoupper($method), self::$supportedMethods)) {
            $this->method = null;
            $this->headers->set('request-method', strtoupper($method));
        } else {
            throw new NotSupportedException(sprintf('The provided request method is not supported:[%s]', $method));
        }
    }

    /**
    * Retrieve the request method
    *
    * @throws NotSupportedException
    */
    public function getMethod(bool $strict = false): ?string
    {
        if (null !== $this->method) {
            return strtoupper($this->method);
        }

        if ($strict) {
            $method = $this->headers->get('x-http-method-override');

            if (! $method) {
                $method = $this->request->get(self::FORCE_METHOD_OVERRIDE_KEY, $this->query->get(self::FORCE_METHOD_OVERRIDE_KEY, 'POST'));
            }
        } else {
            $method = $this->headers->get('request-method');
        }

        if (! $method) {
            return null;
        }

        if (in_array(strtoupper($method), self::$supportedMethods, true)) {
            return $this->method = $method;
        }

        throw new NotSupportedException(sprintf('Requested method is not supported: [%s]', $method));
    }

    /**
    * Set UrlItem object and update params.
    */
    public function setUrl(UrlItem $url): void
    {
        $this->url = $url;

        $this->url->setScheme($this->isRequestSecure() ? 'https' : 'http');

        if (! $this->url->getHost()) {
            $this->url->setHost($this->getHost());
        }

        if (! $this->url->getPort() && ! in_array($this->getPort(), [self::HTTPS_PORT, self::HTTP_PORT])) {
            $this->url->setPort($this->getPort());
        }
    }

    /**
    * Retrieve the Url object.
    */
    public function url(): UrlItem
    {
        return $this->url;
    }

    /**
    * Set Locale.
    *
    * @throws InvalidArgumentException
    */
    public function setLocale(string $locale): void
    {
        // if (class_exists(I18Nv2_Language::class, false)) {
        //     if (! \I18Nv2_Language::isValidCode($locale)) {
        //         throw new InvalidArgumentException(sprintf('Requested locale is not valid: "%s"', $locale));
        //     }
        // }

        if (class_exists(\ResourceBundle::class, false)) {
            if (! in_array($locale, \ResourceBundle::getLocales(''))) {
                throw new InvalidArgumentException(sprintf('Requested locale is not valid: "%s"', $locale));
            }
        }

        $this->locale = $locale;

        if (class_exists(\Locale::class, false)) {
            \Locale::setDefault($locale);
        }
    }

    /**
    * Retrieve the locale.
    */
    public function getLocale(string $locale): string
    {
        if (null !== $this->locale) {
            return $this->locale;
        }

        return self::DEFAULT_SYSTEM_LOCALE;
    }

    /**
    * Retrieve the content type.

    * @throws NotSupportedException
    */
    public function getContentType(): ?string
    {
        $mimeType = $this->headers->get('content-type');

        if (! $mimeType) {
            return null;
        }

        if ($this->isMimeTypeSupported($mimeType)) {
            return $mimeType;
        }

        throw new NotSupportedException(sprintf('Requested mime type is not supported: "%s"', $mimeType));
    }

    /**
    * Retrieve the supported mime type list for a format.
    */
    public function getSupportedMimeTypes(string $format, bool $first = false): array|string|null
    {
        if ($first) {
            return self::$supportedFormats[strtoupper($format)][0] ?? null;
        }

        return self::$supportedFormats[strtoupper($format)] ?? null;
    }

    /**
    * Retrieve the format name that supports the mime type.
    *
    * @throws NotSupportedException
    */
    public function getSupportedMimeTypeFormat(string $mimeType): string
    {
        if (strpos($mimeType, ';') > 0) {
            $mimeType = strtolower(trim(substr($mimeType, 0, strpos($mimeType, ';'))));
        }

        foreach (self::$supportedFormats as $format => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $format;
            }
        }

        throw new NotSupportedException(sprintf('Requested mime type is not supported: "%s"', $mimeType));
    }

    /**
    * Check if a mime type is supported.
    */
    public function isMimeTypeSupported(string $mimeType): bool
    {
        if (strpos($mimeType, ';') > 0) {
            $mimeType = strtolower(trim(substr($mimeType, 0, strpos($mimeType, ';'))));
        }

        foreach (self::$supportedFormats as $format => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return true;
            }
        }

        return false;
    }

    /**
    * Retrieve accepted content types.
    */
    public function getAcceptedContentTypes(bool $first = false): array|string|null
    {
        if (null === $this->acceptedContentTypes) {
            if (empty($accept = $this->headers->get('http-accept'))) {
                return null;
            }

            $this->acceptedContentTypes = explode(',', $accept);
        }

        return !$first
                ? $this->acceptedContentTypes
                : $this->acceptedContentTypes[0];
    }

    /**
    * Check if a content type is accepted.
    */
    public function isContentTypeAccepted(string $mimetype): bool
    {
        if (null !== $types = $this->getAcceptedContentTypes()) {
            return in_array($mimetype, $types);
        }

        return false;
    }

    /**
    * Retrieve accepted encodings.
    */
    public function getAcceptedEncodings(bool $first = false): array|string|null
    {
        if (null === $this->acceptedEncodings) {
            if (empty($accept = $this->headers->get('http-accept-encoding'))) {
                return null;
            }

            $this->acceptedEncodings = explode(',', $accept);
        }

        return !$first
                ? $this->acceptedEncodings
                : $this->acceptedEncodings[0];
    }

    /**
    * Check if a encoding is accepted.
    */
    public function isEncodingAccepted(string $encoding): bool
    {
        if (null !== $encodings = $this->getAcceptedEncodings()) {
            return in_array($encoding, $encodings);
        }

        return false;
    }

    /**
    * Retrieve accepted charset.
    */
    public function getAcceptedCharsets(bool $first = false): array|string|null
    {
        if (null === $this->acceptedCharsets) {
            if (empty($accept = $this->headers->get('http-accept-charset'))) {
                return null;
            }

            $this->acceptedCharsets = explode(',', $accept);
        }

        return !$first
                ? $this->acceptedCharsets
                : $this->acceptedCharsets[0];
    }

    /**
    * Check if a charset is accepted.
    */
    public function isCharsetAccepted(string $charset): bool
    {
        if (null !== $charsets = $this->getAcceptedCharsets()) {
            return in_array($charset, $$charsets);
        }

        return false;
    }

    /**
    * Retrieve accepted language.
    */
    public function getAcceptedLanguages(bool $first = false): array|string|null
    {
        if (null === $this->acceptedLanguages) {
            if (empty($accept = $this->headers->get('http-accept-language'))) {
                return null;
            }

            $this->acceptedLanguages = explode(',', $accept);
        }

        return !$first
                ? $this->acceptedLanguages
                : $this->acceptedLanguages[0];
    }

    /**
    * Check if a language is accepted.
    */
    public function isLanguageAccepted(string $language): bool
    {
        if (null !== $languages = $this->getAcceptedLanguages()) {
            return in_array($language, $languages);
        }

        return false;
    }

    /**
    * Check if the request is secure.
    */
    public function isRequestSecure(): bool
    {
        if ($proto = $this->headers->get('http-x-forwarded-proto')) {
            return in_array(strtolower($proto), ['https', 'on', 'ssl', '1'], true);
        }

        $https = strtolower($this->headers->get('https', '')) ;

        return (($https && $https !== 'off') || $this->headers->get('server-port') === self::HTTPS_PORT);
    }

    /**
    * Check if the request is ajax.
    */
    public function isRequestAjax(): bool
    {
        return (strtolower($this->headers->get('http-x-requested-with', '')) === 'xmlhttprequest');
    }

    /**
    * Check if a method is the request method.
    */
    public function isRequestMethod(string $method): bool
    {
        return ($this->getMethod() === strtoupper($method));
    }

    /**
    * Check if the request method is safe type method.
    */
    public function isRequestMethodSafe(): bool
    {
        return in_array($this->getMethod(), static::$requestTypeSafe, true);
    }

    /**
    * Check if the request method is type with data.
    */
    public function isRequestMethodData(): bool
    {
        return in_array($this->getMethod(), static::$requestTypeData, true);
    }

    /**
    * Check if the request method is read method
    */
    public function isRequestMethodRead(): bool
    {
        return in_array($this->getMethod(), static::$readTypeData, true);
    }

    /**
    * Retrieve request referer or remote address.
    */
    public function getReferer(): ?string
    {
        if (! $referer = $this->headers->get('http-referer')) {
            return $this->headers->get('remote-addr');
        }

        return $referer;
    }

    /**
    * Retrieve request user agent.
    */
    public function getUserAgent(): ?string
    {
        return $this->headers->get('http-user-agent');
    }

    /**
    * Retrieve request protocol version.
    */
    public function getProtocol(): ?string
    {
        return $this->headers->get('server-protocol');
    }

    /**
    * Retrieve request script name.
    */
    public function getScriptName(): ?string
    {
        return $this->headers->find('script-name', 'orig-script-name');
    }

    /**
    * Retrieve request unique id.
    */
    public function getUniqueID(): ?string
    {
        return $this->headers->find('unique-id', 'redirect-unique-id');
    }

    /**
    * Retrieve request document root.
    */
    public function getDocumentRoot(): string
    {
        if (! $root = $this->headers->find('document-root', 'context-document-root')) {
            return \dirname($this->headers->get('script-filename') ?? '');
        }

        return $root;
    }

    /**
    * Retrieve request content.
    */
    public function getContent(): ?string
    {
        if (is_resource($this->requestContent)) {
            rewind($this->requestContent);
            return stream_get_contents($this->requestContent);
        }

        if (empty($this->requestContent)) {
            $this->requestContent = file_get_contents('php://input');
        }

        return $this->requestContent;
    }

    /**
    * Retrieve request port with url port priority.
    */
    public function getPort(): ?int
    {
        if ($urlPort = $this->url->getPort()) {
            return (int)$urlPort;
        }

        if (empty($this->headers->get('http-host'))) {
            return (int)$this->headers->get('server-port');
        }

        return ($this->url->getScheme() === 'https') ? self::HTTPS_PORT : self::HTTP_PORT;
    }

    /**
    * Retrieve request username with url username priority.
    */
    public function getUser(): ?string
    {
        if ($urlUser = $this->url->getUser()) {
            return $urlUser;
        }

        return $this->headers->get('PHP_AUTH_USER');
    }

    /**
    * Retrieve request password with url password priority.
    */
    public function getPassword(): ?string
    {
        if ($urlPassword = $this->url->getPassword()) {
            return $urlPassword;
        }

        return $this->headers->get('PHP_AUTH_PW');
    }

    /**
    * Retrieve request IP address.
    */
    public function getClientIp(bool $strict = true): ?string
    {
        $headers = ['remote-addr'];

        if (! $strict) {
            $headers = array_merge($headers, [ 'http-cf-connecting-ip', 'http-client-ip', 'http-x-forwarded-for' ]);
        }

        return $this->headers->find(...$headers);
    }

    /**
    * Compile the request object.
    */
    public function compile(): HttpRequest
    {
        return $this;
    }
}
