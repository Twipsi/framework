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
use Twipsi\Components\Authentication\Events\AuthenticationFailedEvent;
use Twipsi\Components\Authentication\Events\AuthenticationLoginEvent;
use Twipsi\Components\Authentication\Events\AuthenticationValidatedEvent;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Facades\Event;

class TokenDriver implements AuthDriverInterface
{
    /**
     * The user object.
     */
    protected Authable|null $user = null;

    /**
     * Construct token based authentication driver.
     */
    public function __construct(
        protected IUserProvider    $provider,
        protected HttpRequest|null $request,
        protected string           $apiInputKey,
        protected string           $apiStoreKey,
        protected bool             $hash
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

        // Attempt to retrieve the user.
        if (!is_null($token = $this->getTokenFromRequest())) {
            $this->user = $this->getByToken($token);

            // Dispatch Login event
            Event::dispatch(AuthenticationLoginEvent::class, $this->user);
        }

        return $this->user;
    }

    /**
     * Attempt to validate the user with credentials.
     */
    public function validate(array $credentials)
    {
        if (
            !empty(($token = $credentials[$this->apiInputKey] ?? null)) &&
            ($user = $this->provider->getByCredentials([
                $this->apiStoreKey => $token,
            ]))
        ) {
            // Dispatch Validated event
            Event::dispatch(AuthenticationValidatedEvent::class, $user);

            return true;
        }

        // Dispatch failed attempt event
        Event::dispatch(AuthenticationFailedEvent::class, $credentials);

        return false;
    }

    /**
     * Attempt to find user by the api token.
     */
    protected function getByToken(string $token): ?Authable
    {
        return $this->provider->getByCredentials([
            $this->apiStoreKey => $this->hash ? hash("sha256", $token) : $token,
        ]);
    }

    /**
     * Attempt to find the token in the request.
     */
    protected function getTokenFromRequest(): ?string
    {
        if (
            !($token = $this->request
                ->getInputSource()
                ->get($this->apiInputKey))
        ) {
            $token = $this->request->getPassword();
        }

        return $token;
    }

    /**
     * Set the request object.
     */
    public function setRequest(HttpRequest $request): void
    {
        $this->request = $request;
    }
}
