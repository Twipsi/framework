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

use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\User\Interfaces\IResetable as Resetable;
use Twipsi\Support\Chronos;
use Twipsi\Support\Hasher;

class TokenProvider
{
    /**
     * Construct token provider.
     */
    public function __construct(
        protected IDatabaseConnection $connection,
        protected string              $table,
        protected string              $appKey,
        protected int                 $expires,
        protected int                 $throttle
    ) {
    }

    /**
     * Create a password reset token.
     */
    public function create(Resetable $user): string
    {
        $email = $user->getEmailForPasswordReset();

        // Delete all the prev tokens.
        $this->deleteTokens($user);

        // Create a new reset token.
        $token = $this->createToken();

        // Save the new token to the database.
        $this->connection->open($this->table)->insert([
            "created" => Chronos::date()->getDateTime(),
            "token" => Hasher::hashArgon($token),
            "email" => $email,
        ]);

        return $token;
    }

    /**
     * Check if user has a valid reset token.
     */
    public function hasToken(Resetable $user): bool
    {
        $result = $this->getResetToken($user);

        return $result && !$this->tokenExpired($result["created"]);
    }

    /**
     * Check if reset token is valid.
     */
    public function validateToken(Resetable $user, string $token): bool
    {
        $result = $this->getResetToken($user);

        return $result &&
            !$this->tokenExpired($result["created"]) &&
            Hasher::verifyPassword($token, $result["token"]);
    }

    /**
     * Check if a token has expired.
     */
    protected function tokenExpired(string $expire)
    {
        return Chronos::date()
            ->subSeconds($this->expires)
            ->travel($expire)
            ->isInPast();
    }

    /**
     * Check if a token has expired.
     */
    public function recentlyCreated(Resetable $user)
    {
        $result = $this->getResetToken($user);

        return $result &&
            Chronos::date()
                ->subSeconds($this->throttle)
                ->travel($result["created"])
                ->isInFuture();
    }

    /**
     * Delete all previous reset tokens form the db.
     */
    protected function deleteTokens(Resetable $user): bool
    {
        return 0 <
            $this->connection
                ->open($this->table)
                ->where("email", "=", $user->getEmailForPasswordReset())
                ->delete();
    }

    /**
     * Delete all reset tokens.
     */
    public function delete(Resetable $user): bool
    {
        return $this->deleteTokens($user);
    }

    /**
     * Delete expired reset tokens.
     */
    public function deleteExpired(): int
    {
        $expire = Chronos::date()
            ->subSeconds($this->expires)
            ->getDateTime();

        return $this->connection
            ->open($this->table)
            ->where("created", "<", $expire)
            ->delete();
    }

    /**
     * Get the reset token for a user.
     */
    public function getResetToken(Resetable $user): ?array
    {
        $result = $this->connection
            ->open($this->table)
            ->where("email", "=", $user->getEmailForPasswordReset())
            ->first();

        return $result ? (array) $result : null;
    }

    /**
     * Create a new reset token.
     */
    public function createToken(): string
    {
        return Hasher::hashRandom($this->appKey);
    }

    /**
     * Return the database connection.
     */
    public function getConnection(): IDatabaseConnection
    {
        return $this->connection;
    }
}
