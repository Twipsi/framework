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

use App\Events\Listeners\RouteNotFound;
use Closure;
use Throwable;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Components\Router\Exceptions\RouteNotFoundException;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Components\Router\Router;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Foundation\Middleware\MiddlewareCollector;
use Twipsi\Foundation\Middleware\MiddlewareHandler;
use Twipsi\Foundation\Middleware\MiddlewareLoader;
use Twipsi\Support\Arr;
use Twipsi\Support\Bags\RecursiveArrayBag as Container;

class Kernel
{
    /**
     * The hook's container.
     *
     * @var Container
     */
    protected Container $hooks;

    /**
     * The middleware collection.
     *
     * @var Container
     */
    protected Container $middlewares;

    /**
     * THe router component.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * The middleware component.
     *
     * @var MiddlewareHandler
     */
    protected MiddlewareHandler $middleware;

    /**
     * The bootstrap classes.
     *
     * @var array<int|string>
     */
    protected array $bootstrappers = [
        \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
        \Twipsi\Foundation\Bootstrapers\BootstrapContext::class,
        \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
        \Twipsi\Foundation\Bootstrapers\BootstrapExceptionHandler::class,
        \Twipsi\Foundation\Bootstrapers\SubscribeEventListeners::class,
        \Twipsi\Foundation\Bootstrapers\SubscribeRoutes::class,
    ];

    /**
     * The component bootstrap classes.
     *
     * @var array<int|string>
     */
    protected array $bootcomponents = [
        \Twipsi\Foundation\Bootstrapers\BootstrapAliases::class,
        \Twipsi\Foundation\Bootstrapers\AttachComponentProviders::class,
        \Twipsi\Foundation\Bootstrapers\BootComponentProviders::class
    ];

    /**
     * Construct the kernel.
     *
     * @param Application $app
     */
    public function __construct(protected Application $app)
    {
        $this->hooks = new Container();

        // Load the middlewares.
        $this->middleware = (new MiddlewareLoader($this->app))
            ->load($this->app->path('path.middlewares'));

    }

    /**
     * Run the request through the system and return a valid response.
     *
     * @param HttpRequest $request
     *
     * @return ResponseInterface
     */
    public function run(HttpRequest $request): ResponseInterface
    {
        try {
            $this->app->instance('request', $request);

            // Load the router component.
            $this->router = $this->app->get("route.router");

            // Bootstrap the system.
            $this->bootstrapSystem();

            // Dispatch the router.
            $response = $this->dispatch($request);

        } catch (Throwable $e) {

            // If we have a custom exception handler attached, then
            // collect and handle exceptions with the custom one.
            $response = $this->handleException($request, $e);
        }

        return $response;
    }

    /**
     * Dispatch the router and return the response.
     *
     * @param HttpRequest $request
     *
     * @return ResponseInterface
     * @throws ApplicationManagerException
     * @throws RouteNotFoundException|NotSupportedException
     */
    protected function dispatch(HttpRequest $request): ResponseInterface
    {
        try{
            $route = $this->router->match($request);

        } catch(RouteNotFoundException $e) {

            // Load the system components.
             $this->bootstrapComponents();

            // Return 404 if no route found.
            throw $e;
        }

        // Load the system components.
        $this->bootstrapComponents();

        // Save the middlewares for termination.
        $this->middlewares = $this->handleMiddlewares($this->middleware, $route);
            
        // Render the route and return the response.
        return $this->app->get('response')->make(
            $this->router->render($route)
        );
    }

    /**
     * Handle the middlewares.
     * 
     * @param MiddlewareHandler $handler
     * @param Route $route
     * 
     * @return MiddlewareCollector
     */
    protected function handleMiddlewares(MiddlewareHandler $handler, Route $route): MiddlewareCollector
    {
        // Collect all the middlewares applied for the route.
        $middlewares = $handler->collect($route);

        // Resolve the middleware collection and collect the hooks
        // they sent back to be handled by the response.
        $this->hooks = $handler->resolve($middlewares);

        return $middlewares;
    }

    /**
     * Handle response hooks and send it to the client.
     *
     * @param HttpRequest $request
     * @param ResponseInterface $response
     *
     * @return void
     * @throws ApplicationManagerException
     */
    public function send(HttpRequest $request, ResponseInterface $response): void 
    {
        // Apply all the hooks we have before printing the response.
        foreach (array_reverse($this->hooks->all()) as $closure) {
            if ($closure instanceof Closure) {
                $response = $closure($request, $response);
            }
        }

        // Initiate terminations
        $this->terminate($request, $response);

        // Send the response to the client.
        $response->prepare($request)->send();
    }

    /**
     * Terminate any terminatable.
     *
     * @param HttpRequest $request
     * @param ResponseInterface $response
     *
     * @return void
     * @throws ApplicationManagerException
     */
    public function terminate(HttpRequest $request, ResponseInterface $response): void 
    {
        if(! isset($this->middlewares)) {
            return;
        }

        foreach (Arr::hay($this->middlewares->all())->flatten() as $middleware) {

            if (!is_null($middleware)) {
                $object = $this->app->make($middleware);
    
                if (method_exists($object, "terminate")) {
                    $object->terminate($request, $response);
                }
            }
        }
    }

    /**
     * Handle custom exception handling.
     * @ ToImplement
     */
    protected function handleException(HttpRequest $request, Throwable $e): ResponseInterface 
    {
        $this->app->get(ExceptionHandler::class)->report($e);
        return $this->app->get(ExceptionHandler::class)->render($request, $e);
    }

    /**
     * Bootstrap the application with the provided bootstraps.
     *
     * @return Kernel
     * @throws ApplicationManagerException
     */
    public function bootstrapSystem(): Kernel
    {
        $this->app->bootstrap($this->bootstrappers);

        return $this;
    }

    /**
     * Bootstrap the application with the provided component bootstraps.
     *
     * @return Kernel
     * @throws ApplicationManagerException
     */
    public function bootstrapComponents(): Kernel
    {
        $this->app->bootstrap($this->bootcomponents);

        return $this;
    }
}
