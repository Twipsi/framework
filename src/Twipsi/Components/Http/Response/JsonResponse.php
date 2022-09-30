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

use Twipsi\Components\Http\Response\Response;
use InvalidArgumentException;
use Twipsi\Support\Jso;

class JsonResponse extends Response
{
  /**
  * JSONP callback
  */
  protected string $callback;

  /**
  * JSON Response constructor
  */
  public function __construct(mixed $content, int $code = 200, array $headers = [])
  {
    parent::__construct($content, $code, $headers);
  }

  /**
  * Build response from json string.
  */
  public static function fromString(string $json, int $code = 200, array $headers = []) : Response
  {
    return new self($json, $code, $headers);
  }

  /**
  * Set the document content to json.
  */
  public function setContent(mixed $content) : Response
  {
    $content = Jso::hay($content)->json()
        ? $content
        : $this->convertjson($content);

    return $this->setJson($content);
  }

  /**
  * Sets the JSONP callback.
  * @ Taken From Symphony framework
  *
  * @throws InvalidArgumentException
  */
  public function setCallback(string $callback = null) : Response
  {
    if (null !== $callback) {
      // @ Taken from Symphony framework
      $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*(?:\[(?:"(?:\\\.|[^"\\\])*"|\'(?:\\\.|[^\'\\\])*\'|\d+)\])*?$/u';
      $reserved = [
        'break', 'do', 'instanceof', 'typeof', 'case', 'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 'for', 'switch', 'while',
        'debugger', 'function', 'this', 'with', 'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 'extends', 'super',  'const', 'export',
        'import', 'implements', 'let', 'private', 'public', 'yield', 'interface', 'package', 'protected', 'static', 'null', 'true', 'false',
      ];

      $parts = explode('.', $callback);

      foreach ($parts as $part) {
        if (! preg_match($pattern, $part) || in_array($part, $reserved, true)) {
          throw new InvalidArgumentException('The callback name is not valid.');
        }
      }
    }

    $this->callback = $callback;
    $this->setJson($this->getContent());

    return $this;
  }

  /**
  * Set valid json content and headers.
  */
  protected function setJson(string $content) : Response
  {
    if (! empty($this->callback)) {

      $this->headers->set('content-type', 'application/javascript');
      $content = sprintf('/**/%s(%s);', $this->callback, $content);
    }

    if (! $this->headers->has('content-type') || $this->headers->get('content-type') === 'text/javascript') {
      $this->headers->set('content-type', 'application/json');
    }

    return parent::setContent($content);
  }

  /**
  * Convert data to valid json format.
  */
  protected function convertJson(mixed $content) : string
  {
    $content = Jso::hay($content)->encode(JSON_HEX_TAG);

    if (! Jso::valid()) {
      throw new InvalidArgumentException(Jso::error());
    }

    return $content;
  }

}
