<?php
/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twipsi\Facades\App;
use Twipsi\Facades\Config;
use Twipsi\Facades\Event;
use Twipsi\Facades\Request;
use Twipsi\Facades\Routes;
use Twipsi\Facades\Cookie;
use Twipsi\Facades\Session;
use Twipsi\Components\Events\Interfaces\EventInterface;

/**
 * Define the "app" function helper in global scope.
 */
if (!function_exists("app")) {
    /**
     * Facade function to call services through the application.
     */
    function app(mixed $abstract): mixed
    {
        return App::make($abstract);
    }
}

/**
 * Define the "view" function helper in global scope.
 */
if (!function_exists("request")) {
    /**
     * Facade function to access the request data.
     */
    function request(string $method): mixed
    {
        return Request::getData($method);
    }
}

/**
 * Define the "config" function helper in global scope.
 */
if (!function_exists("config")) {
    /**
     * Facade function to return or set configuration data.
     */
    function config(mixed $argument, $default = null): mixed
    {
        // If we are setting | config( [ cookie.life => 3 ] ).
        if (is_array($argument)) {
            $call = explode(".", array_key_first($argument));

            if (1 < count($call)) {
                return Config::push($call[0], [
                    $call[1] => array_values($argument)[0],
                ]);
            }

            return Config::set($call[0], array_values($argument)[0]);
        }

        // If we are getting | config( cookie.life ).
        if (is_string($argument)) {
            return Config::get($argument, $default);
        }

        return null;
    }
}

/**
 * Define the "event" function helper in global scope.
 */
if (!function_exists("event")) {
    /**
     * Facade function to dispatch event listeners.
     */
    function event(EventInterface|string $event, ...$args): void
    {
        Event::dispatch($event, ...$args);
    }
}

/**
 * Define the "route" function helper in global scope.
 */

if (!function_exists("route")) {
    /**
     * Facade function to access the router.
     */
    function route(string $route): mixed
    {
        if(is_null($url = Routes::byName($route)?->getUrl())) {
          return null;
        }
        
        return rtrim($url, "/");
    }
}

/**
 * Define the "env" function helper in global scope.
 */
if (!function_exists("_env")) {
    /**
     * Facade function to return configuration data.
     */
    function _env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key) ?: ($_SERVER[$key] ?? null);

        if(is_numeric($value)) {
            $value = (int)$value;
        }

        if($value === 'true') {
            $value = true;
        }

        if($value === 'false') {
            $value = false;
        }

        if (! is_null($default)) {
            return $value ?? $default;
        }

        return $value;
    }
}

/**
 * Define the "csrf_token" function helper in global scope.
 */
if (!function_exists("csrf_token")) {
    /**
     * Facade function to get csrf token.
     */
    function csrf_token(): ?string
    {
        return Cookie::getQueuedCookies("_csrf")?->getValue();
    }
}

/**
 * Define the "__" function helper in global scope.
 */
if (!function_exists("__")) {
    /**
     * Facade function to translate data.
     */
    function __(string $text): ?string
    {
        return $text;
    }
}

/**
 * Define the "session" function helper in global scope.
 */
if (!function_exists("session")) {
    /**
     * Facade function to retrieve session data
     */
    function session(string $key): ?string
    {
        return Session::get($key);
    }
}

/**
 * Define the "old" function helper in global scope.
 */
if (!function_exists("old")) {
    /**
     * Facade function to retrieve session input data
     */
    function old(string $key): ?string
    {
        return Session::getInput($key);
    }
}


