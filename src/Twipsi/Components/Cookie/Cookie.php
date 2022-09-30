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

namespace Twipsi\Components\Cookie;

use DateTimeInterface;
use InvalidArgumentException;
use Twipsi\Components\Http\Interfaces\StateProviderInterface;
use Twipsi\Support\Arr;
use Twipsi\Support\Chronos;
use Twipsi\Support\Str;

class Cookie implements StateProviderInterface
{
    /**
     * Invalid name characters. RFC 6265
     */
    private const EXCEPTION_CHARS = '=,; ":\\[]!';

    /**
     * Valid samesite values.
     */
    private const VALID_SAMESITES = ['lax', 'strict', 'none', null];

    /**
     * Name of cookie.
     *
     * @var string
     */
    protected string $name;

    /**
     * Value of cookie.
     *
     * @var string|null
     */
    protected string|null $value;

    /**
     * Expiration date
     *
     * @var int|DateTimeInterface|string
     */
    protected int|DateTimeInterface|string $expires;

    /**
     * Path of cookie
     *
     * @var string
     */
    protected string $path;

    /**
     * Domain of cookie.
     *
     * @var string|null
     */
    protected string|null $domain;

    /**
     * Is cookie secure.
     *
     * @var bool
     */
    protected bool $secure;

    /**
     * Is cookie httpOnly.
     *
     * @var bool
     */
    protected bool $httpOnly;

    /**
     * Samesite of cookie.
     *
     * @var string
     */
    protected string $sameSite;

    /**
     * Encrypt/decrypt cookie.
     *
     * @var bool
     */
    protected bool $raw;

    /**
     * Cookie item Constructor
     *
     * @param string $name
     * @param string|null $value
     * @param mixed $expires
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param bool $raw
     * @param string $sameSite
     */
    public function __construct(string $name, ?string $value = null, mixed $expires = 0, string $path = '/', ?string $domain = null, bool $secure = false, bool $httpOnly = true, bool $raw = false, string $sameSite = 'lax')
    {
        $this->reset($name, $value, $expires, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    /**
     * Initialize or reset cookie object.
     *
     * @param string $name
     * @param string|null $value
     * @param mixed $expires
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param bool $raw
     * @param string $sameSite
     * @return void
     */
    public function reset(string $name, ?string $value = null, mixed $expires = 0, string $path = '/', ?string $domain = null, bool $secure = false, bool $httpOnly = true, bool $raw = false, string $sameSite = 'lax'): void
    {
        $this->raw = $raw;
        $this->name = self::parseName($name);
        $this->value = $value;
        $this->expires = $this->parseExpire($expires);
        $this->path = $this->parsePath($path);
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $this->parseSameSite($sameSite);
    }

    /**
     * Check if cookie name is valid.
     *
     * @param string $name
     * @return string
     */
    private static function parseName(string $name): string
    {
        if (!$name) {
            throw new InvalidArgumentException('The cookies name must be provided, and cannot be empty');
        }

        if (Str::hay($name)->contains(self::EXCEPTION_CHARS)) {
            throw new InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }

        return $name;
    }

    /**
     * Convert cookie expire to unix timestamp.
     *
     * @param int|DateTimeInterface|string $expire
     * @return int
     */
    private function parseExpire(int|DateTimeInterface|string $expire): int
    {
        if ($expire instanceof DateTimeInterface) {
            $expire = $expire->format('U');

        } else if (is_string($expire) && !is_numeric($expire)) {
            $expire = Chronos::date($expire)->stamp();
        }

        return (int)$expire;
    }

    /**
     * Check if cookie path is valid.
     *
     * @param string $path
     * @return string
     */
    private function parsePath(string $path): string
    {
        return !$path ? '/' : $path;
    }

    /**
     * Check if cookie samesite is valid.
     *
     * @param string $sameSite
     * @return string|null
     */
    private function parseSameSite(string $sameSite): ?string
    {
        $sameSite = !$sameSite ? null : strtolower($sameSite);

        if (!in_array($sameSite, self::VALID_SAMESITES)) {
            throw new InvalidArgumentException(sprintf('The provided sameSite value "%s" is not valid.', $sameSite));
        }

        return $sameSite;
    }

    /**
     * Build cookie from raw header.
     *
     * @param string $cookie
     * @param bool $decode
     * @return Cookie
     */
    public static function fromString(string $cookie, bool $decode = false): Cookie
    {
        $required = [
            'path' => '/',
            'httponly' => false,
            'secure' => false,
            'samesite' => 'lax',
            'expires' => 0,
            'domain' => null,
            'max-age' => 0,
            'raw' => !$decode
        ];

        //Check if name contains any illegal chars.
        self::parseName(Str::hay($cookie)->before('='));

        $parameters = Arr::hay(Arr::hay([$cookie])->separate(';'))->pair('=');
        $cookie = array_splice($parameters, 0, 1);

        [$name, $value] = [
            $decode ? urldecode(array_key_first($cookie)) : array_key_first($cookie),
            $decode ? urldecode(array_values($cookie)[0]) : array_values($cookie)[0]
        ];

        $opts = array_merge($required, ['name' => $name, 'value' => $value] + $parameters);

        // If max-age is set and is not 0 then use it to extend expire.
        if (isset($opts['max-age']) && ($opts['max-age'] > 0
                || Chronos::date()->travel($opts['expires'])->secondsPassed() < 1)) {

            $opts['expires'] = Chronos::date()
                    ->setTimezone('GMT')
                    ->addSeconds((int)$opts['max-age'])
                    ->setDateTimeFormat('D, d M Y H:i:s')
                    ->getDateTime() . ' GMT';
        }

        return new self(
            $opts['name'],
            $opts['value'],
            $opts['expires'],
            $opts['path'],
            $opts['domain'],
            (bool)$opts['secure'],
            (bool)$opts['httponly'],
            (bool)$opts['raw'],
            $opts['samesite']
        );
    }

    /**
     * Check if cookie has a value.
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return ! is_null($this->value);
    }

    /**
     * Check if cookie is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return 1 >= (int)$this->expires && (int)$this->expires < time();
    }

    /**
     * Return cookie object as string.
     *
     * @return string
     */
    public function __toString(): string
    {
        // Encode RFC 3986.
        $name = $this->isRaw() ? $this->getName() : rawurlencode($this->getName());

        if (!(string)$this->getValue()) {
            $expire = Chronos::date()->subDays(2)->setDateTimeFormat('D, d M Y H:i:s')->getDateTime() . ' GMT';
            $string = $name . '=deleted;expires=' . $expire . ';Max-Age=0;';

        } else {
            // Encode RFC 3986.
            $value = $this->isRaw() ? $this->getValue() : rawurlencode($this->getValue());

            if ($this->getExpires() !== 0) {
                $string = $name . '=' . $value . ';expires=' . $this->getExpiresAsDate() . ';Max-Age=' . $this->getAge() . ';';

            } else {
                $string = $name . '=' . $value . ';';
            }
        }

        $string .= ' path=' . $this->getPath() . ';';
        $string .= !is_null($this->getDomain()) ? 'domain=' . $this->getDomain() . ';' : '';
        $string .= $this->isSecure() ? 'secure;' : '';
        $string .= $this->isHttpOnly() ? 'httponly;' : '';
        $string .= 'samesite=' . $this->getSameSite() . ';';

        return $string;
    }

    /**
     * Check if we are url encoding.
     *
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->raw;
    }

    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the cookie name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): Cookie
    {
        $this->name = self::parseName($name);

        return $this;
    }

    /**
     * Get the cookie value.
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set cookie value.
     *
     * @param string|null $value
     * @return $this
     */
    public function setValue(?string $value): Cookie
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the cookie expire.
     *
     * @return int
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * Get the cookie expire as date string.
     *
     * @return string
     */
    public function getExpiresAsDate(): string
    {
        return Chronos::date($this->expires)
                ->setTimezone('GMT')
                ->setDateTimeFormat('D, d M Y H:i:s')
                ->getDateTime() . ' GMT';
    }

    /**
     * Get the cookie max-age
     *
     * @return int
     */
    public function getAge(): int
    {
        return max($this->expires - time(), 0);
    }

    /**
     * Get the cookie path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the cookie domain
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Check if cookie is http secure.
     *
     * @return bool|null
     */
    public function isSecure(): ?bool
    {
        return $this->secure;
    }

    /**
     * Set cookie http secure
     *
     * @param bool $mode
     * @return $this
     */
    public function setSecure(bool $mode = true): Cookie
    {
        $this->secure = $mode;

        return $this;
    }

    /**
     * Check if cookie is http only.
     *
     * @return bool|null
     */
    public function isHttpOnly(): ?bool
    {
        return $this->httpOnly;
    }

    /**
     * Get the cookie samesite
     *
     * @return string
     */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }
}
