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

namespace Twipsi\Components\Cookie\Middlewares;

use Closure;
use Twipsi\Components\Cookie\Cookie;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Security\Encrypter;
use Twipsi\Components\Security\Exceptions\DecrypterException;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Support\Jso;

class EncryptCookies implements MiddlewareInterface
{
  /**
  * List of exception cookie names.
  */
  protected array $exceptions = [];

  /**
  * Constructor.
  */
  public function __construct(protected Encrypter $encrypter){}

  /**
  * Resolve middleware logics.
  */
  public function resolve(Request $request, ...$args) : Closure|bool
  {
    // Decrypt request cookies
    $this->decrypt($request);

    // Lazy loaded cookie encryption closure
    // This will be passed to response for later execution
    // This closure can only accept a ResponseInterface object as an argument
    return $this->encrypt(...);
  }

  /**
  * Set decrypted value for encrypted cookies.
  */
  public function decrypt(Request $request) : Request
  {
    foreach ($request->cookies() as $name => $cookie) {

      if (in_array($cookie->getName(), $this->exceptions)) {
        continue;
      }

      if( !$this->isEncrypted( $cookie->getValue() ) ) {
        continue;
      }

      try {
        $decrypted = $this->encrypter->decrypt($cookie->getValue());
        $request->cookies()->get($name)->setValue(! $decrypted ? null : $decrypted);

      } catch (DecrypterException $e) {
        $request->cookies->get($name)->setValue(null);
      }
    }

    return $request;
  }

  /**
  * Check if cookie is encrypted.
  */
  public function isEncrypted(string $value) : bool
  {
    Jso::hay(base64_decode($value))->decode();

    return Jso::valid();
  }

  /**
  * Rebuild cookie with encrypted value.
  */
  public function rebuild(Cookie $cookie, string $encrypted) : Cookie
  {
    $cookie = new Cookie(
      $cookie->getName(),
      $encrypted,
      $cookie->getExpires(),
      $cookie->getPath(),
      $cookie->getDomain(),
      $cookie->isSecure(),
      $cookie->isHttpOnly(),
      false,
      $cookie->getSameSite(),
    );

    return $cookie;
  }

  /**
  * Cookie encryption response hook.
  */
  public function encrypt(Request $request, ResponseInterface $response) : ResponseInterface
  {
    foreach ($response->headers->getCookies() as $cookie) {

      if (in_array($cookie->getName(), $this->exceptions)) {
        continue;
      }

      $response->headers->setCookie(
        $this->rebuild(
          $cookie, $this->encrypter->encrypt($cookie->getValue())
      ));
    }

    return $response;
  }

}
