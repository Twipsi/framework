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

namespace Twipsi\Components\Authentication\Middlewares;

use Closure;
use Twipsi\Components\Authentication\AuthenticationManager;
use Twipsi\Components\Authentication\Drivers\SessionDriver;
use Twipsi\Components\Authentication\Exceptions\AuthenticationException;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class AuthenticateSession implements MiddlewareInterface
{
    /**
     * The authentication driver.
     */
    protected SessionDriver $driver;

    /**
     * Session middleware Constructor
     */
    public function __construct(protected AuthenticationManager $auth)
    {
        $this->driver = $this->auth->driver();
    }

    /**
     * Resolve middleware logics.
     */
    public function resolve(Request $request, ...$args): Closure|bool
    {
        // If no session or user is defined continue.
        if (!$request->hasSession() || !$request->user()) {
            return true;
        }

        // If we logged in with remember cookie.
        if ($this->driver->viaRemember()) {
            $cookie = $request->cookies()
                ->get($this->driver->rememberName())
                ?->getValue();

            $hash = explode("|", $cookie)[2] ?? null;

            if (!$hash || $hash !== $request->user()->getUserHash()) {
                $this->logout($request);
            }
        }

        // If there is no password saved in the session.
        if (!$request->session()->has(
                $this->driver->getAuthContainer().'.'.$this->driver->hashName()
            )) {
            $this->addPasswordToSession($request);
        }

        // If session hash and user hash dont match.
        if (!$this->matchSessionHash($request)) {
            $this->logout($request);
        }

        return true;
    }

    /**
     * Store the user password hash in the active session.
     */
    protected function addPasswordToSession(Request $request): void
    {
        if (!($hash = $request->user()->getUserHash())) {
            return;
        }

        $request
            ->session()
            ->set(
                $this->driver->getAuthContainer().'.'.$this->driver->hashName(),
                $hash
            );
    }

    /**
     * Check if session hash is valid.
     */
    protected function matchSessionHash(Request $request): bool
    {
        return $request->session()->get(
                $this->driver->getAuthContainer().'.'.$this->driver->hashName()
            ) === $request->user()->getUserHash();
    }

    /**
     * Flush and logout.
     */
    protected function logout(Request $request): void
    {
        $this->driver->logout(false);
        $request->session()->flush();

        throw new AuthenticationException(
            "Authentication failed.",
            $this->auth->getDefaultDriver()
        );
    }
}
