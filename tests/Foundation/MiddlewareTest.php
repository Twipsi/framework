<?php

namespace Twipsi\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Router\Route\LoadableRoute;
use Twipsi\Components\Router\Route\Route;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Middleware\MiddlewareCollector;
use Twipsi\Foundation\Middleware\MiddlewareLoader;
use Twipsi\Foundation\Middleware\MiddlewareRepository;
use Twipsi\Foundation\Middleware\MiddlewareResolver;
use Twipsi\Support\Bags\SimpleBag as Container;

class MiddlewareTest extends TestCase
{
    /**
     * The middleware component.
     *
     * @var MiddlewareRepository
     */
    protected MiddlewareRepository $middleware;

    /**
     * The middleware collection.
     *
     * @var Container
     */
    protected Container $middlewares;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $app = new Application();

        $this->middleware = (new MiddlewareLoader($app))
            ->load('./tests/Foundation/Fakes/Middlewares/');
    }

    public function testMiddlewareLoaderShouldReturnRepository()
    {
        $this->assertInstanceOf(
            MiddlewareRepository::class,
            $this->middleware
        );
    }

    public function testRepositoryShouldHoldGeneralMiddlewares()
    {
        $route = $this->createMock(Route::class);

        $route->expects($this->once())
            ->method('getMiddlewares');

        $middlewares = (new MiddlewareCollector($this->middleware))
            ->build($route);

        $this->assertSame(
            $middlewares->all(),
            [
                'general' => [
                    \Twipsi\Components\Http\Middlewares\TrimInput::class,
                    \Twipsi\Components\Http\Middlewares\ConvertEmptyInput::class,
                ]
            ]
        );
    }

    public function testRepositoryShouldHoldGroupMiddlewares()
    {
        $route = (new LoadableRoute('/', fn($v) => 'test route', ['GET']))
            ->middleware('web');

        $middlewares = (new MiddlewareCollector($this->middleware))
            ->build($route);

        $this->assertSame(
            $middlewares->all(),
            [
                'general' => [
                    \Twipsi\Components\Http\Middlewares\TrimInput::class,
                    \Twipsi\Components\Http\Middlewares\ConvertEmptyInput::class,
                ],
                'group' => [
                    \Twipsi\Components\Http\Middlewares\StopForMaintenance::class,
                ]
            ]
        );
    }

    public function testRepositoryShouldHoldSimpleMiddlewares()
    {
        $route = (new LoadableRoute('/', fn($v) => 'test route', ['GET']))
            ->middleware('guest', 'valid');

        $middlewares = (new MiddlewareCollector($this->middleware))
            ->build($route);

        $this->assertSame(
            $middlewares->all(),
            [
                'general' => [
                    \Twipsi\Components\Http\Middlewares\TrimInput::class,
                    \Twipsi\Components\Http\Middlewares\ConvertEmptyInput::class,
                ],
                'single' => [
                    [
                        \Twipsi\Components\Authentication\Middlewares\RedirectIfAuthenticated::class,
                        null,
                    ],
                    [
                        \Twipsi\Components\Authentication\Middlewares\RedirectIfAccountInvalid::class,
                        null,
                    ]
                ]
            ]
        );
    }

    public function testRepositoryProcessSingleMiddlewareArguments()
    {
        $route = (new LoadableRoute('/', fn($v) => 'test route', ['GET']))
            ->middleware('argument');

        $middlewares = (new MiddlewareCollector($this->middleware))
            ->build($route);

        $this->assertSame(
            $middlewares->all(),
            [
                'general' => [
                    \Twipsi\Components\Http\Middlewares\TrimInput::class,
                    \Twipsi\Components\Http\Middlewares\ConvertEmptyInput::class,
                ],
                'single' => [
                    [
                        '\Twipsi\Components\Http\Middlewares\CrossOriginVerify',
                        ['arg1', 'arg2', 'arg3'],
                    ]
                ]
            ]
        );
    }

    public function testRepositoryNonExistantCustomMiddlewareShouldThrowException()
    {
        $route = (new LoadableRoute('/', fn($v) => 'test route', ['GET']))
            ->middleware('argument', '\Test\Component\Custom@arg1,arg2,arg3');

        $this->expectException(RuntimeException::class);

        (new MiddlewareCollector($this->middleware))
            ->build($route);
    }

    public function testRepositoryProcessCustomMiddlewareArguments()
    {
        $route = (new LoadableRoute('/', fn($v) => 'test route', ['GET']))
            ->middleware('\Twipsi\Components\Http\Middlewares\TrimInput@arg1,arg2,arg3');

        $middlewares = (new MiddlewareCollector($this->middleware))
            ->build($route);

        $this->assertSame(
            $middlewares->all(),
            [
                'general' => [
                    \Twipsi\Components\Http\Middlewares\TrimInput::class,
                    \Twipsi\Components\Http\Middlewares\ConvertEmptyInput::class,
                ],
                'custom' => [
                    [
                        '\Twipsi\Components\Http\Middlewares\TrimInput',
                        ['arg1', 'arg2', 'arg3'],
                    ]
                ]
            ]
        );
    }

    public function testResolverShouldResolveMiddlewaresThroughApplicationDI()
    {
        $mdMock = $this->createMock(\Twipsi\Components\Http\Middlewares\TrimInput::class);

        $appMock = $this->createMock(Application::class);
        $appMock->method('make')
            ->willReturn($mdMock);

        $appMock->method('get')
            ->with('request')
            ->willReturn(new HttpRequest());

        $appMock->expects($this->exactly(4))
            ->method('make')
            ->with('\Twipsi\Components\Http\Middlewares\TrimInput::class');

        $collMock = $this->createMock(MiddlewareCollector::class);
        $collMock->method('has')
            ->willReturn(true);
        $collMock->method('get')
            ->willReturn(['\Twipsi\Components\Http\Middlewares\TrimInput::class']);

        $collMock->expects($this->exactly(4))
            ->method('has')->withAnyParameters();

        $collMock->expects($this->exactly(4))
            ->method('get')->withAnyParameters();

        $mdMock->expects($this->exactly(4))
            ->method('resolve')->withAnyParameters();

        (new MiddlewareResolver($appMock))->resolve($collMock);
    }
}