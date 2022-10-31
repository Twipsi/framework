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

namespace Twipsi\Foundation;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Application as SymfonyConsole;
use Throwable;
use Twipsi\Components\Authentication\Exceptions\AuthenticationException;
use Twipsi\Components\Authorization\Exceptions\AuthorizationException;
use Twipsi\Components\File\DirectoryManager;
use Twipsi\Components\Http\Exceptions\AccessDeniedHttpException;
use Twipsi\Components\Http\Exceptions\HttpException;
use Twipsi\Components\Http\Exceptions\HttpResponseException;
use Twipsi\Components\Http\Exceptions\MaliciousRequestException;
use Twipsi\Components\Http\Exceptions\NotFoundHttpException;
use Twipsi\Components\Http\Exceptions\TokenMismatchException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\Response\JsonResponse;
use Twipsi\Components\Http\Response\Response;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;
use Twipsi\Components\Validator\Exceptions\ValidatorException;
use Twipsi\Components\View\ViewErrorBag;
use Twipsi\Facades\Url;
use Twipsi\Foundation\Application\Application;
use Twipsi\Support\Arr;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class ExceptionHandler
{
    /**
     * List of inputs not to flash.
     * 
     * @var array<int|string>
     */
    protected array $nonFlashable = [
        'password',
        'password_confirm',
        'current_password'
    ];

    /**
     * Application Instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Construct Exception Handler.
     * 
     * @param  Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Report an exception.
     *
     * @param Throwable $e
     *
     * @return void
     */
    public function report(Throwable $e): void
    {
        //LOG ERROR;
    }

    /**
     * Render the exception to a response.
     * 
     * @param HttpRequest $request
     * @param Throwable $e
     * 
     * @return Response
     */
    public function render(HttpRequest $request, Throwable $e): Response
    {
        // If we have a response render in the exception use it.
        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return $this->app->get('response')->make($response);
        }

        // Convert the exceptions to their final form before sending it.
        $e = $this->convertException($e);

        return match (true) {
            $e instanceof HttpResponseException => $e->getResponse(),
            $e instanceof AuthenticationException => $this->renderAuthenticationResponse($request, $e),
            $e instanceof ValidatorException => $this->renderValidatorResponse($request, $e),
            default => $this->renderHttpResponse($request, $e),
        };
    }

    /**
     * Render the exception for the console.
     *
     * @param ConsoleOutput $output
     * @param Throwable $e
     * @return void
     */
    public function renderConsoleException(ConsoleOutput $output, Throwable $e): void
    {
        (new SymfonyConsole)->renderThrowable($e, $output);
    }

    /**
     * Convert any placeholder exceptions to their final form.
     *
     * @param Throwable $e
     *
     * @return Throwable
     */
    protected function convertException(Throwable $e): Throwable
    {
        return match (true) {
            $e instanceof RouteNotFoundException
                => new NotFoundHttpException($e->getMessage(), $e),

            $e instanceof AuthorizationException && $e->getStatus() => new HttpException($e->getStatus(), $e->getMessage(), $e),
            $e instanceof AuthorizationException && !$e->getStatus() => new AccessDeniedHttpException($e->getMessage(), $e),
            $e instanceof TokenMismatchException => new HttpException(419, 'Page Expired', $e),
            $e instanceof MaliciousRequestException => new NotFoundHttpException('Bad hostname provided.', $e),
            default => $e,
        };
    }

    /**
     * Build and Http Exception and a final response.
     *
     * @param HttpRequest $request
     * @param Throwable $e
     *
     * @return Response
     */
    protected function renderHttpResponse(HttpRequest $request, Throwable $e): Response
    {
        return $request->isRequestAjax()
            ? $this->buildJsonResponse($e)
            : $this->buildHttpResponse($request, $e);
    }

    /**
     * Redirect user while remembering the intended url,
     * on authentication exception.
     *
     * @param HttpRequest $request
     * @param AuthenticationException $e
     *
     * @return Response
     */
    protected function renderAuthenticationResponse(HttpRequest $request, AuthenticationException $e): Response
    {
        return $request->isRequestAjax()
            ? $this->app->get('response')->json(['message' => $e->getMessage()], 401)
            : $this->app->get('redirector')->remember($e->redirectUrl ?? Url::route('login'));
    }

    /**
     * Redirect back while transmitting the inputs and errors,
     * on validator exception.
     * 
     * @param HttpRequest $request
     * @param ValidatorException $e
     *
     * @return Response
     */
    protected function renderValidatorResponse(HttpRequest $request, ValidatorException $e): Response
    {
        return $request->isRequestAjax()
            ? $this->app->get('response')
                ->json(['message' => $e->getMessage(), 'errors' => $e->errors()], $e->status)

            : $this->app->get('redirector')->to($e->redirectUrl ?? Url::previous())
                ->withInput(Arr::except($request->input()->all(), ...$this->nonFlashable))
                ->withErrors($e->errors());
    }

    /**
     * Build a Json response.
     *
     * @param Throwable $e
     *
     * @return JsonResponse
     */
    protected function buildJsonResponse(Throwable $e): JsonResponse
    {
        return $this->app->get('response')->json(
            $this->buildJsonBody($e),
            ($e instanceof HttpException) ? $e->status : 500,
            ($e instanceof HttpException) ? $e->headers : 500,
        );
    }

    /**
     * Build the Json body.
     *
     * @param Throwable $e
     *
     * @return array<string|mixed>
     */
    protected function buildJsonBody(Throwable $e): array
    {
        // If we are in debug mode return detailed data.
        if (Env::get('APP_DEBUG', false)) {
            return [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        return ['message' => ($e instanceof HttpException) ? $e->getMessage() : 'Server Error'];
    }

    /**
     * Build the HTTP response.
     *
     * @param HttpRequest $request
     * @param Throwable $e
     *
     * @return Response
     */
    protected function buildHttpResponse(HttpRequest $request, Throwable $e): Response
    {
        // If we are in debug mode initiate debug view.
        if (! ($e instanceof HttpException) && Env::get('APP_DEBUG', false)) {
            return $this->prepareResponse($this->buildResponseFromException($e), $request);
        }

        // Build a Http Exception.
        if (! ($e instanceof HttpException)) {
            $e = new HttpException(500, $e->getMessage(), $e);
        }

        return $this->prepareResponse($this->renderHttpException($e), $request);
    }

    /**
     * Render the final exception response based on status code.
     * 
     * @param HttpException $e
     *
     * @return Response
     */
    protected function renderHttpException(HttpException $e): Response
    {
        // If we have a view file to show the exception in.
        if ($file = $this->locateExceptionView($e)) {
            return $this->app->get('response')->view(
                $file,
                [
                    'errors' => new ViewErrorBag(),
                    'exception' => $e,
                ],
                $e->status,
                $e->headers
            );
        }

        // Otherwise render in debug mode...
        return $this->buildResponseFromException($e);
    }

    /**
     * Create a new response from the exception.
     * 
     * @param Throwable $e
     * 
     * @return Response
     */
    protected function buildResponseFromException(Throwable $e): Response
    {
        return new Response(
            Env::get('APP_DEBUG', true) ? $this->renderExceptionWithWhoops($e) : $e->getMessage(),
            ($e instanceof HttpException) ? $e->status : 500,
            ($e instanceof HttpException) ? $e->headers : []
        );
    }

    /**
     * Use Whoops renderer to render the exception.
     * 
     * @param Throwable $e
     * 
     * @return string
     */
    protected function renderExceptionWithWhoops(Throwable $e): string
    {
        $whoops = new Run();

        $pretty = new PrettyPageHandler();
        $pretty->handleUnconditionally(true);
        $pretty->setPageTitle("Whoops");

        $pretty->setApplicationPaths(
            (new DirectoryManager)->list(
                $this->app->path('path.base'), 
                [$this->app->path('path.base').'/vendor']
            )
        );

        $whoops->appendHandler($pretty);
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);

        return $whoops->handleException($e);
    }

    /**
     * Locate the view file for the exception based on status code.
     *
     * @param HttpException $e
     *
     * @return string|null
     */
    protected function locateExceptionView(HttpException $e): ?string
    {
        $possibles = [
            'errors.'.$e->status,
            'errors.'.substr((string)$e->status, 0, 1).'xx',
        ];

        foreach ($possibles as $view) {
            try {
                $this->app->get('view.locator')
                    ->locate($view);

                return $view;

            } catch(Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Wrap up the final response.
     * 
     * @param Response $response
     * @param HttpRequest $request
     * 
     * @return Response
     */
    protected function prepareResponse(Response $response, HttpRequest $request): Response
    {
        return $response->prepare($request);
    }
}
