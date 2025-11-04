<?php

namespace Twipsi\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use stdClass;
use Twipsi\Components\Events\EventHandler;
use Twipsi\Components\File\FileItem;
use Twipsi\Components\Http\Response\ResponseFactory;
use Twipsi\Components\Router\Route\LoadableRoute;
use Twipsi\Components\Router\RouteBag;
use Twipsi\Components\Router\RouteFactory;
use Twipsi\Components\Router\Router;
use Twipsi\Components\Url\Redirector;
use Twipsi\Components\Url\UrlGenerator;
use Twipsi\Facades\Route;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class ApplicationTest extends TestCase
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->app = new Application('/');
    }

    /**
     * Tear down.
     *
     * @return void
     */
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testApplicationShouldRegisterTwipsiPaths()
    {
        $this->assertSame($this->app->path('path.base'), '/');
        $this->assertSame($this->app->path('path.environment'), '/');
        $this->assertSame($this->app->path('path.boot'), '/\boot');
        $this->assertSame($this->app->path('path.app'), '/\app');
        $this->assertSame($this->app->path('path.config'), '/\config');
        $this->assertSame($this->app->path('path.database'), '/\database');
        $this->assertSame($this->app->path('path.middlewares'), '/\middleware');
        $this->assertSame($this->app->path('path.locale'), '/\locale');
        $this->assertSame($this->app->path('path.public'), '/\public');
        $this->assertSame($this->app->path('path.resources'), '/\resources');
        $this->assertSame($this->app->path('path.assets'), '/\public\assets');
        $this->assertSame($this->app->path('path.routes'), '/\routes');
        $this->assertSame($this->app->path('path.storage'), '/\storage');
        $this->assertSame($this->app->path('path.cache'), '/\storage/cache');
    }

    public function testApplicationPathRegistryShouldBeExtendable()
    {
        $this->assertSame($this->app->nav()->configPath('/context'), '/\config/context');
    }

    public function testApplicationShouldBindSelfToInstanceRegistry()
    {
        $this->assertInstanceOf(Application::class, $this->app->get('app'));
    }

    public function testApplicationComponentsShouldBeAccessible()
    {
        $this->assertInstanceOf(Application::class, $this->app->get('app'));
        $this->assertInstanceOf(Application::class, $this->app['app']);
        $this->assertInstanceOf(Application::class, $this->app->app);
    }

    public function testApplicationComponentsShouldBeSettable()
    {
        $this->app->set('test', new stdClass());
        $this->app['test2'] = new stdClass();
        $this->app->test3 = new stdClass();

        $this->assertInstanceOf(stdClass::class, $this->app->get('test'));
        $this->assertInstanceOf(stdClass::class, $this->app->get('test2'));
        $this->assertInstanceOf(stdClass::class, $this->app->get('test3'));

        $this->expectException(ApplicationManagerException::class);
        $this->app->get('test4');
    }

    public function testApplicationShouldLoadBaseComponents()
    {
        $this->assertInstanceOf(RouteFactory::class, $this->app->get('route.factory'));
        $this->assertInstanceOf(Router::class, $this->app->get('route.router'));
        $this->assertInstanceOf(RouteBag::class, $this->app->get('route.routes'));
        $this->assertInstanceOf(EventHandler::class, $this->app->get('events'));
        $this->assertInstanceOf(UrlGenerator::class, $this->app->get('url'));
        $this->assertInstanceOf(Redirector::class, $this->app->get('redirector'));
        $this->assertInstanceOf(ResponseFactory::class, $this->app->get('response'));
    }

    public function testApplicationShouldLoadHelpers()
    {
        Route::get('/admin/test', fn() => 'hello')->name('test');

        $this->assertSame(route('test'), '/admin/test');
    }

    public function testApplicationShouldHandleBootCachePaths()
    {
        $this->assertSame($this->app->configurationCacheFile(), '/\boot/cache/config.php');
        $this->assertSame($this->app->routeCacheFile(), '/\boot/cache/routes.php');
        $this->assertSame($this->app->componentCacheFile(), '/\boot/cache/components.php');
        $this->assertSame($this->app->eventsCacheFile(), '/\boot/cache/events.php');
    }

    public function testApplicationShouldFindAnAliasBasedOnClass()
    {
        $this->assertTrue($this->app->aliases()
            ->alias(\Twipsi\Components\View\ViewLocator::class));

        $this->assertFalse($this->app->aliases()
            ->alias(\Twipsi\Components\View\ViewEngine::class));
    }

    public function testApplicationShouldFindAnAliasNameBasedOnClass()
    {
        $this->assertSame('view.locator', $this->app->aliases()
            ->resolve(\Twipsi\Components\View\ViewLocator::class));

        $this->assertSame(\Twipsi\Components\View\ViewEngine::class, $this->app->aliases()
            ->resolve(\Twipsi\Components\View\ViewEngine::class));
    }

    public function testApplicationShouldReturnAliasClassesBasedOnName()
    {
        $this->assertSame(\Twipsi\Components\View\ViewLocator::class, $this->app->aliases()
            ->concrete('view.locator'));

        $this->assertSame(\Twipsi\Components\View\ViewEngine::class, $this->app->aliases()
            ->concrete(\Twipsi\Components\View\ViewEngine::class));
    }

    public function testApplicationShouldResolveAliasOnAnyPartProvided()
    {

    }
}