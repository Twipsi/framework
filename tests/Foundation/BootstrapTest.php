<?php

namespace Twipsi\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Twipsi\Components\File\FileItem;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Router\Route\LoadableRoute;
use Twipsi\Facades\Event;
use Twipsi\Foundation\Application\Application;
use Twipsi\Tests\Foundation\Fakes\Events\TestEvent;

class BootstrapTest extends TestCase
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

    public function testApplicationShouldKnowIfSystemWasBootstrapped()
    {
        $_SERVER['CACHE_EVENTS'] = true;
        $_SERVER['CACHE_ROUTES'] = true;

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\SubscribeEventListeners::class,
            \Twipsi\Foundation\Bootstrapers\SubscribeRoutes::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.events', './tests/Foundation/Fakes/Events/Listeners');
        $this->app->nav()->set('path.routes', './tests/Router/fake');
        $this->app->setApplicationNamespace('\Twipsi\Tests\Foundation\Fakes');

        $this->app->bootstrap($bootstrappers);
        $this->assertTrue($this->app->isRoutesCached());
        $this->assertTrue($this->app->isEventsCached());

        unset($_SERVER['CACHE_EVENTS'], $_SERVER['CACHE_ROUTES']);
    }

    public function testApplicationShouldBootConfigLazyLoaded()
    {
        $_SERVER['CACHE_CONFIG'] = true;

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.config', './tests/Config/fake');

        if($this->app->isConfigurationCached()) {
            (new FileItem($this->app->configurationCacheFile()))
                ->delete();
        }

        $this->app->bootstrap($bootstrappers);
        $this->assertFalse($this->app->isConfigurationCached());

        $this->app->get('config')
            ->get('system.test');

        $this->assertTrue($this->app->isConfigurationCached());

        unset($_SERVER['CACHE_CONFIG']);
    }

    public function testConfigBootstrapperShouldSetApplicationEnvironmentLoader()
    {
        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.config', './tests/Config/fake');
        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->getEnvironment(), 'testing');
    }

    public function testEnvironmentBootstrapperShouldLoadDefaultEnvironment()
    {
        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
        ];

        $this->app->nav()->set('path.environment', './tests/Foundation/Fakes');
        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->environmentFile(), './tests/Foundation/Fakes/.env');
        $this->assertSame(_env('APP_NAME'), 'Twipsi');
    }

    public function testEnvironmentBootstrapperShouldLoadServerOverriddenEnvironment()
    {
        unset($_SERVER['APP_NAME']); unset($_ENV['APP_NAME']);
        $_SERVER['APP_ENV'] = 'testing';

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
        ];

        $this->app->nav()->set('path.environment', './tests/Foundation/Fakes');
        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->environmentFile(), './tests/Foundation/Fakes/.env.testing');
        $this->assertSame(_env('APP_NAME'), 'TwipsiTesting');

        unset($_SERVER['APP_ENV']);
    }

    public function testContextBootstrapperShouldSetTestContextIfTestingEnvironment()
    {
        $_SERVER['APP_ENV'] = 'testing';

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
            \Twipsi\Foundation\Bootstrapers\BootstrapContext::class,
        ];

        $this->app->nav()->set('path.environment', './tests/Foundation/Fakes');
        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->getContext(), 'testcase');

        unset($_SERVER['APP_ENV']);
    }

    public function testApplicationShouldLoadRouteBasedContext()
    {
        $route = new LoadableRoute('/', fn()=>'test', ['GET', 'POST']);
        $route->setContext('routebased');

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
            \Twipsi\Foundation\Bootstrapers\BootstrapContext::class,
        ];

        $this->app->nav()->set('path.environment', './tests/Foundation/Fakes');
        $this->app->bootstrap($bootstrappers);
        $this->app->poststrap([]);

        $this->app->instance(\Twipsi\Components\Router\Route\Route::class, $route);

        $this->assertSame($this->app->getContext(), 'routebased');
        $this->app->flush();
    }

    public function testApplicationShouldLoadConfigurationBasedOnContext()
    {
        $_SERVER['APP_ENV'] = 'testing';

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapEnvironment::class,
            \Twipsi\Foundation\Bootstrapers\BootstrapContext::class,
            \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
        ];

        $this->app->nav()->set('path.environment', './tests/Foundation/Fakes');
        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.config', './tests/Config/fake');

        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->get('config')->get('system.name'), 'twipsi_testing_context');

        unset($_SERVER['APP_ENV']);
    }

    public function testApplicationShouldBootExceptionHandler()
    {
        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapExceptionHandler::class,
        ];
        $this->app->bootstrap($bootstrappers);

        $this->assertSame(error_reporting(), -1);
        $this->expectException(\ErrorException::class);

        trigger_error('Test error.',E_USER_ERROR);
    }

    public function testApplicationShouldBootEventListeners()
    {
        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\SubscribeEventListeners::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.events', './tests/Foundation/Fakes/Events/Listeners');
        $this->app->setApplicationNamespace('\Twipsi\Tests\Foundation\Fakes');

        $this->app->bootstrap($bootstrappers);

        ob_start();
        Event::dispatch(TestEvent::class);
        $return = ob_get_clean();

        $this->assertSame('[EVENT] => Notification sent.', $return);
    }

    public function testApplicationShouldBootRoutes()
    {
        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\SubscribeRoutes::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.routes', './tests/Router/fake');
        $this->app->setApplicationNamespace('\Twipsi\Tests\Foundation\Fakes');

        $this->app->bootstrap($bootstrappers);

        $request = (new HttpRequest(['request-uri' => 'admin/login']));
        $request->setMethod('GET');

        $route = $this->app->get('route.router')
            ->setRequest($request)
            ->match($request);

        $this->assertSame($route->getName(), 'fakeroute');
    }

    public function testApplicationShouldInjectConfigAliases()
    {
        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
            \Twipsi\Foundation\Bootstrapers\BootstrapAliases::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.config', './tests/Config/fake');

        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->aliases()->get('identifier.token'),
            \Twipsi\Tests\Foundation\Fakes\ADriver::class);
    }

    public function testApplicationShouldRegisterComponentProviders()
    {
        $_SERVER['CACHE_CONFIG'] = false;
        $_SERVER['CACHE_COMPONENTS'] = false;

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
            \Twipsi\Foundation\Bootstrapers\AttachComponentProviders::class,
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.config', './tests/Config/fake');

        $this->app->bootstrap($bootstrappers);

        $this->assertSame($this->app->components()->always(),
        [
            \Twipsi\Foundation\ComponentProviders\CookieProvider::class,
            \Twipsi\Foundation\ComponentProviders\SessionProvider::class,
        ]);
    }

    public function testApplicationShouldBootComponentProviders()
    {
        $_SERVER['CACHE_CONFIG'] = false;
        $_SERVER['CACHE_COMPONENTS'] = false;

        $bootstrappers = [
            \Twipsi\Foundation\Bootstrapers\BootstrapConfiguration::class,
            \Twipsi\Foundation\Bootstrapers\AttachComponentProviders::class,
            \Twipsi\Foundation\Bootstrapers\BootComponentProviders::class
        ];

        $this->app->nav()->set('path.boot', './tests/Foundation/Fakes/Boot');
        $this->app->nav()->set('path.config', './tests/Config/fake');

        $this->app->bootstrap($bootstrappers);

        $this->assertTrue($this->app->components()->isBooted());
    }
}