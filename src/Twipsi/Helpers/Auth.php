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
use Twipsi\Facades\Access;

/**
 * Define the "auth" function helper in global scope.
 */
if (!function_exists("auth")) {
    /**
     * Facade function to call authentication.
     */
    function auth(): mixed
    {
        return App::get('auth.driver');
    }
}

/**
 * Define the "access" function helper in global scope.
 */
if (!function_exists("access")) {
    /**
     * Facade function to call access manager.
     */
    function access(string|array $action, mixed ...$args): mixed
    {
        return Access::check($action, ...$args);
    }
}

/**
 * Define the "auth" function helper in global scope.
 */
if (!function_exists("user")) {
    /**
     * Facade function to call authentication.
     */
    function user(string $property): mixed
    {
        return App::get('user')->{$property};
    }
}
