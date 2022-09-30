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

namespace Twipsi\Components\Security\Csrf\Middlewares;

use Closure;
use InvalidArgumentException;
use Twipsi\Components\Http\Exceptions\TokenMismatchException;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\Middlewares\Tools\ExceptionChecker;
use Twipsi\Components\Security\Csrf\CookieTokenProvider;
use Twipsi\Components\Security\Csrf\Interfaces\TokenProviderInterface;
use Twipsi\Components\Security\Csrf\SessionTokenProvider;
use Twipsi\Components\Security\Encrypter;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Support\Chronos;

class CsrfTokenVerification implements MiddlewareInterface
{
  /**
  * Wether to reset token on every call.
  * @option REFRESH_ON_REQUEST | REFRESH_ON_TIMEOUT
  */
  protected const CSRF_MODE = 'REFRESH_ON_TIMEOUT';

  /**
  * Method to use for csrf auth
  * @option COOKIE | SESSION
  */
  protected const CSRF_METHOD = 'SESSION';

  /**
  * Csrf cookie key.
  */
  public const CSRF_COOKIE_KEY = '_csrf';

  /**
  * Csrf session key.
  */
  public const CSRF_SESSION_KEY = '_csrf';

  /**
  * Csrf key in input data.
  */
  public const CSRF_INPUT_KEY = 'csrf_token';

  /**
  * Csrf key in header.
  */
  public const CSRF_HEADER_KEY = 'X-CSRF-TOKEN';

  /**
  * Csrf alternate key in header.
  */
  public const ENCRYPTED_CSRF_HEADER_KEY = 'X-XSRF-TOKEN';

  /**
  * Esception list
  */
  protected array $exceptions = [];

  /**
  * The token provider object.
  */
  protected TokenProviderInterface $tokenProvider;

  /**
  * Constructor
  */
  public function __construct(protected ConfigRegistry $configuration, protected Encrypter $encrypter){}

  /**
  * Build the appropriate token provider.
  */
  public function buildTokenProvider(Request $request) : TokenProviderInterface
  {
    if (self::CSRF_METHOD === 'COOKIE') {

      return new CookieTokenProvider(
        $request->cookies->get(self::CSRF_COOKIE_KEY),
        $this->configuration->get('security.csrf_length')
      );
    }

    if (self::CSRF_METHOD === 'SESSION') {

      return new SessionTokenProvider(
        $request->session(),
        self::CSRF_SESSION_KEY,
        $this->configuration->get('security.csrf_length')
      );
    }

    throw new InvalidArgumentException(sprintf("the provided csrf mode is not supported... [%s]", self::CSRF_METHOD));
  }

  /**
  * Resolve middleware logics.
  */
  public function resolve(Request $request, ...$args) : Closure|bool
  {
    // Build the token provider
    $this->tokenProvider = $this->buildTokenProvider($request);

    if (ExceptionChecker::url($request->url(), $this->exceptions)
        || $this->isReadMethod($request)
        || $this->tokensMatch($request)) {

      $this->handleCsrfToken($request);
      return true;
    }

    throw new TokenMismatchException('Invalid CSRF-token provided', 419);
  }

  /**
  * Check if url method is type read.
  */
  protected function isReadMethod(Request $request) : bool
  {
    return $request->isRequestMethodRead();
  }

  /**
  * Check if csrf tokens match.
  */
  protected function tokensMatch(Request $request) : bool
  {
    $token = $this->pullTokenFromRequest($request);

    return $this->tokenProvider->validateToken((string)$token);
  }

  /**
  * Attempt to retrieve csrf token from request.
  */
  protected function pullTokenFromRequest(Request $request) : string
  {
    $token = $request->request->get(self::CSRF_INPUT_KEY) ?? $request->headers->get(self::CSRF_HEADER_KEY);

    if (! $token && $header = $request->headers->get(self::ENCRYPTED_CSRF_HEADER_KEY)) {
      $token = $this->decryptToken($header);
    }

    return $token ?? '';
  }

  /**
  * Decrypt header token.
  */
  protected function decryptToken(string $header) : string
  {
    return $this->encrypter->decrypt($header) ?? '';
  }

  /**
  * Refresh and queue the cookie token if chosen.
  */
  protected function handleCsrfToken(Request $request) : Request
  {
    // Generate new token on every request
    if (self::CSRF_MODE === 'REFRESH_ON_TIMEOUT') {
      $this->tokenProvider->refreshToken();
    }

    // Save token to session if we are using session
    if (self::CSRF_METHOD === 'SESSION') {
      $request->session()->generateCsrf($this->tokenProvider->getToken());
    }

    if (self::CSRF_METHOD === 'COOKIE') {
      $request->session->delete(self::CSRF_SESSION_KEY);
    }

    // Add token to a cookie and pass it to the response
    $this->queueCookieForResponse($request);

    return $request;
  }

  /**
  * Queue our token cookie for the response.
  */
  protected function queueCookieForResponse(Request $request) : Request
  {
    $request->cookies()->queue(self::CSRF_COOKIE_KEY,
      $this->tokenProvider->getToken(),
      Chronos::date()->addMinutes($this->configuration->get('session.lifetime'))->stamp()
    );

    return $request;
  }

}
