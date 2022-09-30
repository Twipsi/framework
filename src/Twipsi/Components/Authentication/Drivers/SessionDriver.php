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

namespace Twipsi\Components\Authentication\Drivers;

use Twipsi\Components\Authentication\Drivers\Interfaces\AuthDriverInterface;
use Twipsi\Components\Authentication\Events\AuthenticationAttemptedEvent;
use Twipsi\Components\Authentication\Events\AuthenticationAuthenticatedEvent;
use Twipsi\Components\Authentication\Events\AuthenticationFailedEvent;
use Twipsi\Components\Authentication\Events\AuthenticationLoginEvent;
use Twipsi\Components\Authentication\Events\AuthenticationLogoutEvent;
use Twipsi\Components\Authentication\Events\AuthenticationValidatedEvent;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Session\SessionItem;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Facades\Event;
use Twipsi\Support\Chronos;
use Twipsi\Support\KeyGenerator;

class SessionDriver implements AuthDriverInterface
{
    /**
     * Name of array containing auth data.
     */
    protected const AUTHCONTAINER = "_auth";

    /**
     * The user object.
     */
    protected Authable|null $user = null;

    /**
     * If we retrieved the user via remember me method.
     */
    protected bool $viaRemember = false;

    /**
     * If we attempted to retrieve user via remember me method.
     */
    protected bool $rememberAttempted = false;

    /**
     * Remember token cookie duration (minutes)
     */
    protected int $rememberDuration = 2628000;

    /**
     * Construct session based authentication driver.
     */
    public function __construct(
        protected string           $name,
        protected IUserProvider    $provider,
        protected SessionItem      $session,
        protected HttpRequest|null $request = null
    ) {
    }

    /**
     * Check if we have an authenticated user.
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Check if we do not have an authenticated user.
     */
    public function guest(): bool
    {
        return is_null($this->user());
    }

    /**
     * Check if we have a user logged in already.
     */
    public function loggedIn(): bool
    {
        return !is_null($this->user);
    }

    /**
     * Get the authenticated user.
     */
    public function user(): ?Authable
    {
        // Return the retrieved user if we already retrieved it.
        if (!is_null($this->user)) {
            return $this->user;
        }

        // Attemp to retieve the user.
        if (is_null($this->user = $this->getFromSessionID())) {

            if (!is_null($this->user = $this->getFromCookieID())) {

                $this->updateSession($this->user->getUserID());

                // Dispatch Login event
                Event::dispatch(AuthenticationLoginEvent::class, $this->user);
            }
        }

        return $this->user;
    }

    /**
     * Attempt to find user by the session auth id.
     */
    protected function getFromSessionID(): ?Authable
    {
        if (is_null($id = $this->session->get(
                self::AUTHCONTAINER.'.'.$this->authenticationName()
            ))) {
            return null;
        }

        if (!is_null($user = $this->provider->getByID($id))) {
            // Dispatch Authenticated event
            Event::dispatch(AuthenticationAuthenticatedEvent::class, $user);
        }

        return $user;
    }

    /**
     * Attempt to find user by the cookie remember id.
     */
    protected function getFromCookieID(): ?Authable
    {
        if (is_null($this->request)) {
            return null;
        }

        if (
            $cookie = $this->request
                ->cookies()
                ->get($this->rememberName())
                ?->getValue()
        ) {
            if (
                !is_string($cookie) ||
                count($segments = explode("|", $cookie)) !== 3 ||
                $this->rememberAttempted
            ) {
                return null;
            }

            $id = (int) $segments[0];
            $token = $segments[1];

            $this->viaRemember = !is_null(
                $user = $this->provider->getByToken($id, $token)
            );
            $this->rememberAttempted = true;
        }

        return $user ?? $this->user;
    }

    /**
     * Attempt to login user with provided credentials.
     */
    public function attempt(array $credentials, bool $viaRemember = false): bool
    {
        // Dispatch attempt event
        Event::dispatch(AuthenticationAttemptedEvent::class, $credentials);

        // Get the requested user without auth
        $user = $this->provider->getByCredentials($credentials);

        // Check if the credentials are valid and login.
        if (
            !is_null($user) &&
            $this->validateUserCredentials($user, $credentials)
        ) {
            return $this->login($user, $viaRemember);
        }

        // Dispatch failed attempt event
        Event::dispatch(AuthenticationFailedEvent::class, $credentials);

        return false;
    }

    /**
     * Login the requested validated user.
     */
    public function login(Authable $user, bool $viaRemember = false): bool
    {
        $this->updateSession($user->getUserID());

        if ($viaRemember) {
            $this->refreshRememberToken($user);
            $this->queueRememberCookie($user);
        }

        $this->user = $user;

        // Dispatch Login event
        Event::dispatch(AuthenticationLoginEvent::class, $this->user);

        return true;
    }

    /**
     * Logout the validated user.
     * If strict we will logout from all devices.
     * 
     * @param bool $strict
     * 
     * @return void
     */
    public function logout(bool $strict = true): void
    {
        if (!($user = $this->user())) {
            return;
        }

        $this->removeAuthenticatedUser();

        // If we change the remember token the user will be
        // logged out from every device that contains the remember me token.
        if ($strict && !is_null($user) && !empty($user->getRememberToken())) {
            $this->refreshRememberToken($user, true);
        }

        // Dispatch logout event
        Event::dispatch(AuthenticationLogoutEvent::class, $user);

        $this->user = null;
    }

    /**
     * Attempt to validate the user credentials.
     */
    protected function validateUserCredentials(
        Authable $user,
        array $credentials
    ) {
        if ($valid = $this->provider->validateUser($user, $credentials)) {
            // Dispatch Validated event
            Event::dispatch(AuthenticationValidatedEvent::class, $user);
        }

        return $valid;
    }

    /**
     * Create or update a user remember token.
     */
    protected function refreshRememberToken(Authable $user, bool $strict = false): void 
    {
        if (empty($user->getRememberToken()) || $strict) {
            $user->setRememberToken(
                $token = KeyGenerator::generateSecureKey(60)
            );
            
            $this->provider->updateUserToken($user, $token);
        }
    }

    /**
     * Queue the cookie containing the remember token.
     */
    protected function queueRememberCookie(Authable $user): void
    {
        $token =
            $user->getUserID() .
            "|" .
            $user->getRememberToken() .
            "|" .
            $user->getUserHash();

        $this->request->cookies()->queue(
            $this->rememberName(),
            $token,
            Chronos::date()
                ->addMinutes($this->getRememberDuration())
                ->stamp()
        );
    }

    /**
     * Set the Id to the session and refresh it.
     */
    protected function updateSession(int $id): void
    {
        $this->session->set(
            self::AUTHCONTAINER.'.'.$this->authenticationName(),
            $id
        );
        $this->session->refresh(true);
    }

    /**
     * Remove user from storage(s).
     */
    protected function removeAuthenticatedUser(): void
    {
        $this->session->delete(self::AUTHCONTAINER);

        $this->request->cookies()->expire($this->rememberName());
    }

    /**
     * Get the unique identifier that holds authentication data.
     */
    public function authenticationName(): string
    {
        return "_auth_" . $this->name . "_" . sha1(static::class);
    }

    /**
     * Get the unique identifier that holds remember token data.
     */
    public function rememberName(): string
    {
        return "twipsi_remember_" . $this->name . "_" . sha1(static::class);
    }

    /**
     * Get the unique identifier that holds hash data.
     */
    public function hashName(): string
    {
        return "_hash_" . $this->name . "_" . sha1(static::class);
    }

    /**
     * Get the validity duration for the remember cookie.
     */
    public function getRememberDuration(): int
    {
        return $this->rememberDuration;
    }

    /**
     * Set the validity duration for the remember cookie.
     */
    public function setRememberDuration(int|null $minutes): void
    {
        $this->rememberDuration = !is_null($minutes)
            ? $minutes
            : $this->rememberDuration;
    }

    /**
     * Check if user was logged in via remember token
     */
    public function viaRemember(): bool
    {
        return $this->viaRemember;
    }

    /**
     * Set the request object.
     */
    public function setRequest(HttpRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * Set the session object.
     */
    public function setSession(SessionItem $session): void
    {
        $this->session = $session;
    }

    /**
     * Get the authentication container name.
     */
    public function getAuthContainer(): string
    {
        return self::AUTHCONTAINER;
    }
}
