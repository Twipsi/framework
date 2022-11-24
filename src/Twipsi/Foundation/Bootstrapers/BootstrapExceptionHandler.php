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

namespace Twipsi\Foundation\Bootstrapers;

use Closure;
use ErrorException;
use ReflectionException;
use Throwable;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\Env;
use Twipsi\Foundation\ExceptionHandler;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Tests\Foundation\Fakes\FakeException;

class BootstrapExceptionHandler
{
    /**
     * The application object.
     * 
     * @var Application
     */
    protected static Application $app;

    /**
     * Construct Bootstrapper.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        static::$app = $app;
    }

    /**
     * Invoke the bootstrapper.
     *
     * @return void
     */
    public function invoke(): void
    {
        // Set PHP error handling configurations.
        error_reporting(-1);

        set_error_handler($this->redirectCall('handleError'));

        set_exception_handler($this->redirectCall('handleException'));

        register_shutdown_function($this->redirectCall('handleShutdown'));

        if (! Env::get('APP_ENV', 'testing')) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Render the exception for the console.
     *
     * @param Throwable $e
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function console(Throwable $e): void
    {
        $this->getExceptionHandler()
            ->renderConsoleException(new ConsoleOutput(), $e);
    }

    /**
     * Handle incoming errors.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     *
     * @return void
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        if(error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle incoming exceptions.
     *
     * @param Throwable $e
     * @return void
     * @throws ApplicationManagerException
     * @throws ReflectionException
     */
    public function handleException(Throwable $e): void 
    {
        var_dump('HANDLING EXCEPTION');

        // Send it to the logger.
        $this->getExceptionHandler()
            ->report($e);

        // Render the actual exception.
        $this->getExceptionHandler()
            ->render(static::$app->get('request'), $e)
            ->send();
    }

    /**
     * Handle Shutdown.
     *
     * @return void
     * @throws ApplicationManagerException|ReflectionException
     */
    public function handleShutdown(): void 
    {
        // If no error then abort.
        if(is_null($error = error_get_last())) {
            return;
        }

        // Check if the error is a fatal error.
        if(in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE])) {
            $this->handleException(
                new FatalError($error['message'], 0, $error, 0)
            );
        }
    }

    /**
     * Redirect call to a class method loader.
     * 
     * @param string $method
     * @return Closure
     */
    protected function redirectCall(string $method): Closure
    {
        return fn(...$args) => $this->{$method}(...$args);
    }

    /**
     * Get the exception handler component.
     *
     * @return ExceptionHandler
     * @throws ApplicationManagerException|ReflectionException
     */
    protected function getExceptionHandler(): ExceptionHandler
    {
        // This is extended by the application handler in twipsi.
        return static::$app->make(
            ExceptionHandler::class
        );
    }
}
