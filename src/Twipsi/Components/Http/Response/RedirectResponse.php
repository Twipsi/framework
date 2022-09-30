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

use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\Response\Response;
use InvalidArgumentException;
use Twipsi\Components\File\UploadedFile;
use Twipsi\Components\View\ViewErrorBag;

class RedirectResponse extends Response
{
    /**
     * THe request object.
     *
     * @var HttpRequest
     */
    protected HttpRequest $request;

  /**
  * Redirect response constructor.
  */
  public function __construct(string $url, int $code = 302, array $headers = [])
  {
    parent::__construct('', $code, $headers);

    $this->setLocation($url);

    if (!$this->isRedirect()) {
      throw new InvalidArgumentException(sprintf('The provided status code is not a valid redirect code [%s]', $code));
    }
  }

  /**
  * Set the document content to a valid redirect content.
  */
  public function setLocation(string $url) : Response
  {
    if (empty($url)) {
      throw new InvalidArgumentException('The requested url can not be empty');
    }

    $this->headers->set('Location', $url);
    $this->setContent($this->redirectContent($url));

    return $this;
  }

  /**
   * Redirect while adding flash messages.
   * 
   * @param mixed $key
   * @param null $value
   * 
   * @return static
   */
  public function withFlash($key, $value = null): static
  {
    $keys = is_null($value) ? $key : [$key => $value];

    foreach($keys as $key => $value) {
      $this->request->session()->flash($key, $value);
    }

    return $this;
  }

  /**
   * Redirect while adding input data.
   * 
   * @param null|array $inputs
   * 
   * @return static
   */
  public function withInput(?array $inputs = null): static
  {
    $this->request->session()->input(
      $this->cleanInputs($inputs ?? $this->request->input())
    );

    return $this;
  }

  /**
   * Redirect while adding error data.
   * 
   * @param array $errors
   * 
   * @return static
   */
  public function withErrors(array $errors): static
  {
    $this->request->session()->errors(
      new ViewErrorBag($errors, $this->request->session()->getErrors())
    );

    return $this;
  }

  /**
   * Clean the input data.
   * 
   * @param array $inputs
   * 
   * @return array
   */
  protected function cleanInputs(array $inputs): array
  {
    foreach($inputs as $input => $value)
    {
      if(is_array($value)) {
        $inputs[$input] = $this->cleanInputs($value);
      }

      if($value instanceof UploadedFile) {
        unset($inputs[$input]);
      }
    }

    return $inputs;
  }

  /**
  * Return default valid redirect response content.
  */
  public function redirectContent(string $url) : string
  {
    return sprintf(
      '<!DOCTYPE html>
      <html>
        <head>
          <meta charset="UTF-8" />
          <meta http-equiv="refresh" content="0;url=\'%1$s\'" />
          <title>Redirecting to %1$s</title>
        </head>
        <body>
          Redirecting to <a href="%1$s">%1$s</a>.
        </body>
      </html>',
      htmlspecialchars($url, \ENT_QUOTES, 'UTF-8')
    );
  }

    /**
     * Set the request object.
     *
     * @param HttpRequest $request
     * @return RedirectResponse
     */
    public function setRequest(HttpRequest $request): RedirectResponse
  {
      $this->request = $request;

      return $this;
  }

}
