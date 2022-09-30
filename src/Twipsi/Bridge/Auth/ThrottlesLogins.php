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

namespace Twipsi\Bridge\Auth;

use Twipsi\Components\Authentication\Events\AuthenticationLockoutEvent;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface as Response;
use Twipsi\Components\RateLimiter\RateLimiter;
use Twipsi\Components\Validator\Exceptions\ValidatorException;
use Twipsi\Facades\App;
use Twipsi\Facades\Event;
use Twipsi\Facades\Translate;
use Twipsi\Support\Str;

trait ThrottlesLogins
{
    /**
     * Check if we have stepped over the limit.
     * 
     * @param HttpRequest $request
     * @return bool
     */
    protected function hasSteppedOverMaxAttempts(HttpRequest $request): bool
    {
        return $this->limiter()->isOverTheLimit(
            $this->throttleKey($request),
            $this->maxAttempts()
        );
    }

    /**
     * Increment login attempt.
     *
     * @param HttpRequest $request
     * @return void
     */
    protected function incrementLoginAttempt(HttpRequest $request): void
    {
        $this->limiter()->makeAttempt(
            $this->throttleKey($request),
            $this->decayInSeconds()
        );
    }

    /**
     * Clear the login attempts cache file.
     * 
     * @param HttpRequest $request
     * @return void
     */
    protected function clearLoginAttempt(HttpRequest $request): void
    {
        $this->limiter()->clearLimiter($this->throttleKey($request));
    }

    /**
     * Abort with validation error.
     *
     * @param HttpRequest $request
     * @return Response
     * @throws ValidatorException
     */
    protected function abortWithLockoutResponse(HttpRequest $request): Response 
    {
        $remaining = $this->limiter()->getLockoutTimer($this->throttleKey($request));

        throw ValidatorException::with([
            $this->identifier() => Translate::get('authentication.lockout', [
                'seconds' => $remaining,
                'minutes' => ceil($remaining / 60),
                ]
            )]
        )->status(429)->redirect($this->redirectPath());
    }

    /**
     * Dispatch the lockout event.
     * 
     * @param HttpRequest $request
     * @return void
     */
    protected function dispatchLockoutEvent(HttpRequest $request): void
    {
        Event::dispatch(AuthenticationLockoutEvent::class, $request);
    }

    /**
     * Build the cache key we should use.
     * 
     * @param HttpRequest $request
     * @return string
     */
    protected function throttleKey(HttpRequest $request): string 
    {
        return Str::hay(strtolower(
            $request->input($this->identifier()).'|'.$request->getClientIp()
        ))->transliterate();
    }


    /**
     * Get the maximum attempts allowed.
     * 
     * @return int
     */
    protected function maxAttempts(): int 
    {
        return property_exists($this, 'maxAttempts') ? $this->maxAttempts : 5;
    }

    /**
     * Get the decay value to use in seconds.
     * 
     * @return int
     */
    protected function decayInSeconds(): int 
    {
        return property_exists($this, 'decayInMinutes') ? $this->decayInMinutes*60 : 60;
    }

    /**
     * Return the limiter service.
     * 
     * @return RateLimiter
     */
    protected function limiter(): RateLimiter
    {
        return App::get('ratelimiter');
    }
}
