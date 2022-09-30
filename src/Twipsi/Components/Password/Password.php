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

namespace Twipsi\Components\Password;

use Closure;
use Twipsi\Bridge\User\ModelUser;
use Twipsi\Bridge\User\User;
use Twipsi\Components\Notification\Exceptions\NotificationException;
use Twipsi\Components\Password\Events\PasswordResetEvent;
use Twipsi\Components\Password\Events\PasswordSentEvent;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Components\User\Interfaces\IResetable as Resetable;
use Twipsi\Facades\Event;
use Twipsi\Support\Hasher;
use Twipsi\Support\Str;

class Password
{
    /**
     * The new password input key to use.
     */
    protected const PASSWORDKEY = "password";

    /**
     * The password reset input key to use.
     */
    protected const TOKENKEY = "token";

    public const USER_NOT_FOUND = "password.notfound";

    public const RESET_THROTTLED = "password.throttled";

    public const NOTIFY_FAILED = "password.notify";

    public const RESET_LINK_SENT = "password.sent";

    public const SUCCESSFULL_RESET = "password.successfull";

    public const ILLEGAL_TOKEN = "password.illegal";

    /**
     * Construct token provider.
     */
    public function __construct(
        protected TokenProvider $tokenProvider,
        protected IUserProvider $user,
        protected string        $table
    ) {
    }

    /**
     * Attempt to send the password reset notification.
     * 
     * @param array $credentials
     * 
     * @return string
     */
    public function sendPasswordResetLink(array $credentials): string
    {
        // Check if the user has a valid password reset token.
        if (!($user = $this->getUser($credentials))) {
            return self::USER_NOT_FOUND;
        }

        // Check if the reset token is throttled.
        if ($this->tokenProvider->recentlyCreated($user)) {
            return self::RESET_THROTTLED;
        }

        $token = $this->createToken($user);

        // Attempt to notify user about the event.
        try {
            $user->sendPasswordResetNotification($token);
        } catch (NotificationException $e) {
            return self::NOTIFY_FAILED;
        }

        // Dispatch Password reset sent event.
        Event::dispatch(PasswordSentEvent::class, $user);

        return self::RESET_LINK_SENT;
    }

    /**
     * Attempt to reset the password.
     */
    public function resetPassword(array $credentials, Closure $callback = null): string
    {
        // Check if the user has a valid password reset token.
        if (is_string($user = $this->validateResetToken($credentials))) {
            return $user;
        }

        // Build hash from password.
        $hash = Hasher::hashArgon($credentials[self::PASSWORDKEY]);

        // If user is a base user.
        if ($user instanceof User) {
            // Save the new password for the user.
            $user->setUserHash($hash);

            $this->tokenProvider
                ->getConnection()
                ->open($this->table)
                ->where($user->getIDColumn(), "=", $user->getUserID())
                ->update([$user->getHashColumn() => $hash]);

        } elseif ($user instanceof ModelUser) {
            $user->set($user->getHashColumn(), $hash)->save();
        }

        // Delete all the reset tokens for the user.
        $this->deleteToken($user);

        // Dispatch Password reset event.
        Event::dispatch(PasswordResetEvent::class, $user);

        // If we have any callbacks to do.
        if(! is_null($callback)) {
            call_user_func($callback, $user);
        }

        return self::SUCCESSFULL_RESET;
    }

    /**
     * Check if the requested user has a valid request token.
     */
    protected function validateResetToken(array $credentials): string|Resetable
    {
        if (!($user = $this->getUser($credentials))) {
            return self::USER_NOT_FOUND;
        }

        if (!$this->hasValidToken($user, $credentials[self::TOKENKEY])) {
            return self::ILLEGAL_TOKEN;
        }

        return $user;
    }

    /**
     * Attempt to retrieve the current user requested.
     */
    protected function getUser(array $credentials): ?Resetable
    {
        // Remove the token field because it wont match with the saved data.
        $credentials = array_filter(
            $credentials,
            function ($k) {
                return !Str::hay($k)->resembles(self::TOKENKEY) &&
                    !Str::hay($k)->resembles(self::PASSWORDKEY);
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this->user->getByCredentials($credentials);
    }

    /**
     * Check if password reset was created recently.
     */
    public function recent(Resetable $user): bool
    {
        return $this->tokenProvider->recentlyCreated($user);
    }

    /**
     * Create a new reset token and save it to db.
     */
    public function createToken(Resetable $user): string
    {
        return $this->tokenProvider->create($user);
    }

    /**
     * Create a new reset token and save it to db.
     */
    public function deleteToken(Resetable $user): bool
    {
        return $this->tokenProvider->delete($user);
    }

    /**
     * Check if user has a password reset token.
     */
    public function hasToken(Resetable $user): bool
    {
        return $this->tokenProvider->hasToken($user);
    }

    /**
     * Check if user has a valid password reset token.
     */
    public function hasValidToken(Resetable $user, string $token): bool
    {
        return $this->tokenProvider->validateToken($user, $token);
    }
}
