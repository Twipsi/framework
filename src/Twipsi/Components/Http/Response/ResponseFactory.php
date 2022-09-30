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
use Twipsi\Components\Http\Response\JsonResponse;
use Twipsi\Components\Http\Response\ViewResponse;
use Twipsi\Components\Http\Response\FileResponse;
use Twipsi\Components\Url\Redirector;

use Twipsi\Support\Bags\RecursiveArrayBag;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;

class ResponseFactory
{
  /**
  * Response factory constructor.
  */
  public function __construct(protected Redirector $redirector){}

  /**
  * Make a response based on the content.
  */
  public function make(mixed $content, int $code = 200, array $headers = []) : ResponseInterface
  {
    // If we have a response object as a content.
    if ($content instanceof ResponseInterface) {
      return $content;
    }

    if ($content instanceof \Stringable) {
      $headers = array_merge($headers, ['Content-Type' => 'text/html']);
      return $this->build($content->__toString(), $code,  $headers);
    }

    if ($content instanceof RecursiveArrayBag
      || is_array($content)) {

      return $this->json($content, $code, $headers = []);
    }

    $headers = array_merge($headers, ['Content-Type' => 'text/html']);
    return $this->build($content, $code, $headers);
  }

  /**
  * Build a json response if the content is an array.
  */
  public function build(mixed $content, int $code = 200, array $headers = []) : ResponseInterface
  {
    return new Response($content, $code, $headers);
  }

  /**
  * Build an empty response (informational).
  */
  public function empty(int $code = 204, array $headers = []) : ResponseInterface
  {
    return new Response('', $code, $headers);
  }

  /**
  * Build a view response.
  */
  public function view(string $view, array $data = [], int $code = 200, array $headers = []) : ViewResponse
  {
    return new ViewResponse($view, $data, $code, $headers);
  }

  /**
  * Build a json response if the content is an array.
  */
  public function json(array $content = [], int $code = 200, array $headers = []) : JsonResponse
  {
    return new JsonResponse($content, $code, $headers);
  }

  /**
  * Build a jsonp response if the content is an array.
  */
  public function jsonp(string $callback, array $content = [], int $code = 200, array $headers = []) : JsonResponse
  {
    return $this->json($content, $code, $headers)->setCallback($callback);
  }

  /**
  * Create a file response.
  */
  public function file(string $file, array $headers = []) : FileResponse
  {
    return new FileResponse($file, 200, $headers);
  }

  /**
  * Create a file download response.
  */
  public function download(string $file, ?string $name = null, array $headers = [], $disposition = 'attachment') : FileResponse
  {
    $response = (new FileResponse($file, 200, $headers))->disposition($disposition);

    if (null !== $name) {
      $response->setName();
    }

    return $response;
  }

  /**
  * Redirect to a custom url.
  */
  public function redirectToUrl(string $url, int $code = 302, array $headers = [], ?bool $secure = null)
  {
    return $this->redirector->to($url, $code, $headers, $secure);
  }

  /**
  * Redirect to a named route.
  */
  public function redirectToRoute(string $name, int $code = 302, array $headers = [])
  {
    return $this->redirector->route($name, $headers, $code);
  }

  /**
  * Redirect to a controller action.
  */
  public function redirectToAction(string $action, array $parameters = [], int $code = 302, array $headers = [])
  {
    return $this->redirector->route($action, $parameters, $code, $headers);
  }

  /**
  * Redirect a guest while saving the intended url.
  */
  public function redirectGuest(string $url, int $code = 302, array $headers = [])
  {
    return $this->redirector->remember($url, $code, $headers);
  }

  /**
  * Redirect tot he intended url.
  */
  public function redirectToIntended(string $fallback = '/', int $code = 302, array $headers = [])
  {
    return $this->redirector->intended($fallback, $code, $headers);
  }

}
