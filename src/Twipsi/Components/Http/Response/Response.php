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

namespace Twipsi\Components\Http\Response;

use \Datetime;
use \DateTimeZone;
use Twipsi\Components\Http\Response\ResponseHeaderBag;
use Twipsi\Components\Http\HttpRequest as Request;
use InvalidArgumentException;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Facades\Config;
use Twipsi\Support\Arr;
use Twipsi\Support\Str;
use Twipsi\Support\Chronos;

class Response implements ResponseInterface, \Stringable
{
  /**
  * List of valid Http response codes.
  */
  protected const HTTP_RESPONSE_CODES = [

    // Informational 1xx
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',
    103 => 'Early Hints',

    // Successful 2xx
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',
    208 => 'Already Reported',
    226 => 'IM Used',

    // Redirection 3xx
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Switch Proxy',
    307 => 'Temporary Redirect',
    308 => 'Permanent Redirect',

    // Client Error 4xx
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Content Too Large',
    414 => 'URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',
    419 => 'Page Expired', // Custom
    421 => 'Misdirected Request',
    422 => 'Unprocessable Content',
    423 => 'Locked',
    424 => 'Failed Dependency',
    425 => 'Too Early',
    426 => 'Upgrade Required',
    428 => 'Precondition Required',
    429 => 'Too Many Requests',
    431 => 'Request Header Fields Too Large',
    451 => 'Unavailable For Legal Reasons',

    // Server Error 5xx
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates',
    507 => 'Insufficient Storage',
    508 => 'Loop Detected',
    510 => 'Not Extended',
    511 => 'Network Authentication Required',
  ];

  /**
  * List of cachable status codes.
  */
  protected const HTTP_RESPONSE_CACHEABLE_RESPONSES = [
    200, 203, 300, 301, 302, 404, 410
  ];

  /**
  * List of status codes that point to a redirect.
  */
  protected const HTTP_RESPONSE_REDIRECT_CODES = [
    201, 301, 302, 303, 307, 308
  ];

  /**
  * List of headers that should not be sent if response is 304.
  */
  protected const HTTP_RESPONSE_304_HEADER_EXCEPTIONS = [
    'Allow',
    'Content-Encoding',
    'Content-Language',
    'Content-Length',
    'Content-MD5',
    'Content-Type',
    'Last-Modified'
  ];

  /**
  * List of response codes that should not have a content.
  */
  protected const HTTP_RESPONSE_NO_CONTENT = [
    204,304
  ];

  /**
  * Header bag with headers to be sent.
  */
  public ResponseHeaderBag $headers;

  /**
  * Http response status text.
  */
  public string $text;

  /**
  * Http response content charset.
  */
  public string $charset;

  /**
  * Http protocol version.
  */
  public string $version;

  /**
  * Response constructor
  */
  public function __construct(protected null|array|string $content, protected int $code = 200, array $headers = [])
  {
    $this->headers = new ResponseHeaderBag($headers);

    $this->setContent($content);
    $this->setCode($code);
    $this->setVersion('1.1');
    $this->setCharset('UTF-8');
  }

  /**
  * Return response as a compiled string.
  */
  public function __toString() : string
  {
    return
    sprintf('HTTP/%s %s %s', $this->version, $this->code, $this->text).
    '\r\n'.$this->headers.'\r\n'.
    $this->getContent();
  }

  /**
  * Prepare and fix up the response before sending.
  */
  public function prepare(Request $request) : Response
  {
    // Fix protocol version
    if ('HTTP/1.1' !== $request->headers->get('server-protocol')) {
      $this->setVersion('2');
    }

    // If cache control is no-cache expire cache.
    if ($this->headers->cacheControl()->has('no-cache')) {
      $this->headers->set('pragma', 'no-cache');
      $this->headers->set('expires', -1);
    }

    // If we have https set the cookies secure.
    if ($request->isRequestSecure()) {
      foreach ($this->headers->getCookies() as $cookie) {
        $cookie->setSecure();
      }
    }

    // If response doesnt need content.
    if($this->isInformational() || in_array($this->code, self:: HTTP_RESPONSE_NO_CONTENT)) {

      $this->setContent( null );
      $this->headers->delete('content-type');
      $this->headers->delete('content-length');
      ini_set('default_mimetype', '');

      return $this;
    }

    // Fix content type if not provided.
    $charset = $this->charset ?: 'UTF-8';
    if (! $this->headers->has('content-type')) {
      $this->headers->set('content-type', 'text/html; charset='.$charset);
    }

    // Fix the content length
    if ($this->headers->has('transfer-encoding')) {
      $this->headers->delete('content-length');
    }

    if ($request->getMethod() === 'HEAD') {
      $this->setContent(null);
    }

    return $this;
  }

  /**
  * Send the response headers to the client.
  */
  public function sendHeaders() : Response
  {
    // If server has sent the headers exit.
    if(true === headers_sent()) {
      return $this;
    }

    // Send the http status header.
    header($this->buildStatusHeader(), true, $this->code);

    // Send all the headers with overrite besides cookies.
    foreach ($this->headers->camelize() as $header => $values) {

      $override = $header === 'Set-Cookie' ? false : true;
      $values = is_array($values) ? $values : [$values];

      foreach ($values as $value) {
        header($header.': '.$value, $override, $this->code);
      }
    }

    return $this;
  }

  /**
  * Send the response to the client.
  */
  public function send() : Response
  {
    $this->sendHeaders();
    echo $this->content;

    if (\function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();

    } else if (! in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
      $this->closeOutputBuffers(0);
    }

    return $this;
  }

  /**
  * Build the http status header.
  */
  private function buildStatusHeader() : string
  {
    return sprintf('HTTP/%s %s %s', $this->version, $this->code, $this->text);
  }

  /**
  * Set the document content.
  */
  public function setContent(mixed $content) : Response
  {
    $this->content = $content ?? '';

    return $this;
  }

  /**
  * Get the document content.
  */
  public function getContent() : string
  {
    return $this->content;
  }

  /**
  * Set the http protocol version.
  */
  public function setVersion(string $version) : Response
  {
    $this->version = $version;

    return $this;
  }

  /**
  * Get the http protocol version.
  */
  public function getVersion() : string
  {
    return $this->version;
  }

  /**
  * Set the http response status code.
  */
  public function setCode(int $code, string $text = null) : Response
  {
    if (! Arr::has(self::HTTP_RESPONSE_CODES, $code)) {
      throw new InvalidArgumentException(sprintf("The requested response code [%s] is not valid", $code));
    }

    $this->code = $code;

    if(empty($text)) {
      $this->text = self::HTTP_RESPONSE_CODES[ $code ] ?? 'unknown status';

    } else {
      $this->text = $text;
    }

    return $this;
  }

  /**
  * Get the http response status code.
  */
  public function getCode() : int
  {
    return $this->code;
  }

  /**
  * Set the http response charset.
  */
  public function setCharset(string $charset) : Response
  {
    $this->charset = $charset;

    return $this;
  }

  /**
  * Get the http response charset.
  */
  public function getCharset() : string
  {
    return $this->charset;
  }

  /**
  * Check if we can cache the response
  */
  public function isCacheable() : bool
  {
    if (! Arr::exists(self::HTTP_RESPONSE_CACHEABLE_RESPONSES, $this->code)
        || $this->headers->cacheControl()->has('no-store')
        || $this->headers->cacheControl()->has('private')
      ){
      return false;
    }

    if ($this->headers->has('Last-Modified') || $this->headers->has('Etag')) {
      return true;
    }

    return $this->isFresh();
  }

  /**
  * Check if the response is considered "Fresh".
  */
  public function isFresh() : bool
  {
    return $this->getLifeTime() > 0;
  }

  /**
  * Make the reponse stale by setting the Age header.
  */
  public function makeStale() : Response
  {
    if($this->isFresh()) {
      $this->headers->set('Age', $this->getMaxAge());
      $this->headers->delete('Expires');
    }

    return $this;
  }

  /**
  * Check if response needs revalidation.
  */
  public function mustRevalidate()
  {
    return
      $this->headers->cacheControl()->has('must-revalidate')
      || $this->headers->cacheControl()->has('proxy-revalidate');
  }

  /**
  * Return the Maximum age of the response in seconds.
  */
  public function getMaxAge() :? int
  {
    // Find s-maxage first since it overrites max-age
    if ($this->headers->cacheControl()->has('s-maxage')) {
      return (int)$this->headers->cacheControl()->get('s-maxage');
    }

    if ($this->headers->cacheControl()->has('max-age')) {
      return (int)$this->headers->cacheControl()->get('max-age');
    }

    // If no cache directive age is set attempt to figure it out from headers
    return $this->headers->has('Expires') && $this->headers->has('Date')
      ? Chronos::date($this->getExpires())
                  ->travel($this->headers->get('Date'))
                  ->secondsPassed()
      : null;
  }

  /**
  * Return the Age of the response in seconds.
  */
  public function getAge() : int
  {
    if ($this->headers->has('Age')) {
      return (int)$this->headers->get('Age');
    }

    return (int) max( $this->headers->has('Date')
     ? Chronos::date()->travel($this->headers->get('Date'))->secondsPassed()
     : Chronos::date()->getSeconds(), 0 );
  }

  /**
  * The Expires HTTP header contains the date/time after which the
  * response is considered expired.
  * Note - If the value is 0 the response is considered expired.
  * Format should be 'Wed, 21 Oct 2015 07:28:00 GMT'.
  */
  public function getExpires() :? string
  {
    if (! $this->headers->has('Expires')) {
      return null;
    }

    if ('0' !== $expire = $this->headers->get('Expires')) {
      return $expire;
    }

    return Chronos::date()
            ->subDays(2)
            ->setDateTimeFormat('D, d M Y H:i:s')
            ->getDateTime().' GMT';
  }

  /**
  * Set the Expires header value of the response.
  * Format should be 'Wed, 21 Oct 2015 07:28:00 GMT'.
  */
  public function setExpires(DateTime|string $date = null) : Response
  {
    if (null === $date) {
      $this->headers->delete('Expires');

      return $this;
    }

    $date = Chronos::date($date)
            ->setTimezone(Config::get('system.timezone', 'UTC'))
            ->setDateTimeFormat('D, d M Y H:i:s')
            ->getDateTime();

    $this->headers->set('Expires', $date.' GMT');

    return $this;
  }

  /**
  * The Date general HTTP header contains the date and time at which
  * the message was originated.
  * Format should be 'Wed, 21 Oct 2015 07:28:00 GMT'.
  */
  public function setDate(DateTime|string $date) : Response
  {
    $date = Chronos::date($date)
            ->setTimezone(Config::get('system.timezone', 'UTC'))
            ->setDateTimeFormat('D, d M Y H:i:s')
            ->getDateTime();

    $this->headers->set('Date', $date.' GMT');

    return $this;
  }

  /**
  * Get the remaining time the response is considered fresh  in seconds before
  * the response turns stale and will need revalidation.
  */
  public function getLifeTime() :? int
  {
    $maxAge = $this->getMaxAge();

    return null !== $maxAge ? $maxAge - $this->getAge() : null;
  }

  /**
  * The Last-Modified response HTTP header contains a date and time
  * when the origin server believes the resource was last modified.
  * Format should be 'Wed, 21 Oct 2015 07:28:00 GMT'.
  */
  public function setLastModified(DateTime|string $date = null) : Response
  {
    if (null === $date) {
      $this->headers->delete('Last-Modified');

      return $this;
    }

    $date = Chronos::date($date)
            ->setTimezone(Config::get('system.timezone', 'UTC'))
            ->setDateTimeFormat('D, d M Y H:i:s')
            ->getDateTime();

    $this->headers->set('Last-Modified', $date.' GMT');

    return $this;
  }

  /**
  * The ETag (or entity tag) HTTP response header is an identifier
  * for a specific version of a resource.
  */
  public function setEtag(string $etag = null, bool $weak = false) : Response
  {
    if (null === $etag) {
      $this->headers->delete('Etag');

      return $this;
    }

    if (! Str::hay($etag)->first() === '"') {
      $etag = Str::hay($etag)->wrap('"');
    }

    $this->headers->set('Etag', (!$weak ? $etag : 'W/'.$etag));

    return $this;
  }

  /**
  * Converts the response to a valid 304 response.
  */
  public function setNotModified() : Response
  {
    $this->setCode(304);
    $this->setContent(null);

    $this->headers->replace(
      array_diff($this->headers->all(), self::HTTP_RESPONSE_304_HEADER_EXCEPTIONS)
    );

    return $this;
  }

  /**
  * Check if response status code is valid.
  */
  public function isValid() : bool
  {
    return $this->code < 100 || $this->code > 511;
  }

  /**
  * Check if response status code is informational.
  */
  public function isInformational() : bool
  {
    return $this->code >= 100 && $this->code < 200;
  }

  /**
  * Check if response status code is successfull.
  */
  public function isSuccessfull() : bool
  {
    return $this->code >= 200 && $this->code < 300;
  }

  /**
  * Check if response status code is a redirect.
  */
  public function isRedirect() : bool
  {
    return Arr::exists(self::HTTP_RESPONSE_REDIRECT_CODES, $this->code);
  }

  /**
  * Check if response status code is a redirection.
  */
  public function isRedirection() : bool
  {
    return $this->code >= 300 && $this->code < 400;
  }

  /**
  * Check if response status code is a server error.
  */
  public function isServerError() : bool
  {
    return $this->code >= 500 && $this->code < 600;
  }

  /**
  * Check if response status code is a client error.
  */
  public function isClientError() : bool
  {
    return $this->code >= 400 && $this->code < 500;
  }

  /**
  * Check if response status code is a not found.
  */
  public function isNotFound() : bool
  {
    return 404 === $this->code;
  }

  /**
  * Check if response status code is a forbidden.
  */
  public function isForbidden() : bool
  {
    return 403 === $this->code;
  }

  /**
  * Cleans or flushes output buffers up to target level.
  * @ Taken From Symphony framework
  */
  public function closeOutputBuffers(int $targetLevel): void
  {
    $status = ob_get_status(true);
    $level = \count($status);
    $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | \PHP_OUTPUT_HANDLER_FLUSHABLE;

    while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
      ob_end_flush();
    }
  }

}
?>
