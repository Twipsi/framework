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

namespace Twipsi\Components\Session\Middlewares;

use Closure;
use Twipsi\Components\Cookie\Cookie;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Session\Drivers\ArraySessionDriver;
use Twipsi\Components\Session\SessionHandler;
use Twipsi\Components\Session\SessionItem;
use Twipsi\Foundation\Middleware\MiddlewareInterface;
use Twipsi\Support\Chronos;

class StartSession implements MiddlewareInterface
{
  /**
  * Session middleware Constructor
  */
  public function __construct(protected SessionHandler $handler){}

  /**
  * Resolve middleware logics.
  */
  public function resolve(Request $request, ...$args) : Closure|bool
  {
    // If no driver is set in the configuration
    if(! $this->handler->isConfigured()) {
      return true;
    }

    // Initialize session driver and session item
    // Get ID from cookie and set to session
    $this->appendSessionToRequest($request,
      $session = $this->startSession($request)
    );

    // Remove garbage sessions
    $this->disposeGarbage($session);

    return true;
  }

  /**
  * Set current url to persist.
  */
  private function persistUrl(Request $request) : void
  {
    if ($request->getMethod() === 'GET' && !$request->isRequestAjax()) {
      $request->session()->setPreviousUrl($request->url()->getPath());
    }
  }

  /**
  * Set session to request object for accessibility.
  */
  private function appendSessionToRequest(Request $request, SessionItem $session) : void
  {
    $request->setSession($session);
  }

  /**
  * Get unique id from Cookie and set it.
  */
  private function setSessionWorkerID(Request $request, SessionItem $session) : void
  {
    if (null !== $cookie = $request->cookies()->get($session->name())) {
      $session->setId($cookie->getValue());
    }
  }

  /**
  * Initialize session drivers.
  */
  private function startSession(Request $request) : SessionItem
  {
    $session = $this->handler->driver();
    $this->setSessionWorkerID($request, $session);

    // Initialize Session
    return $session->start();
  }

  /**
  * Put session ID to cookie and save session driver.
  */
  private function saveSession(Request $request, ResponseInterface $response)
  {
    // Set current uri to session to permit moving backwards.
    $this->persistUrl($request);

    $request->session()->save();

    return $this->setSessionIDToCookie($request, $response);
  }

  /**
  * Remove app wide garbage sessions.
  */
  private function disposeGarbage(SessionItem $session) : void
  {
    $validity = $this->handler->config('session.lifetime');
    $session->driver()->clean($validity);
  }

  /**
  * Build Cookie with session id and append to response.
  */
  private function setSessionIDToCookie(Request $request, ResponseInterface $response) : ResponseInterface
  {
    $session = $request->session();

    if($session->driver() instanceof ArraySessionDriver) {
      return $response;
    }

    $response->headers->setCookie(new Cookie(

      $session->name(),
      $session->id(),
      Chronos::date()->addMinutes($this->handler->config('session.lifetime'))->stamp(),
      $this->handler->config('cookie.path') ?? '/',
      $this->handler->config('cookie.domain') ?? '',
      $this->handler->config('cookie.secure') ?? true,
      $this->handler->config('cookie.httpOnly') ?? false,
      false,
      $this->handler->config('cookie.same_site') ?? 'lax',
    ));

    return $response;
  }

  /**
   * Kernel termination.
   * 
   * @param Request $request
   * @param ResponseInterface $response
   * 
   * @return [type]
   */
  public function terminate(Request $request, ResponseInterface $response): void
  {
    $this->saveSession($request, $response);
  }

}
