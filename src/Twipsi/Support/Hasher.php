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

namespace Twipsi\Support;

use Error;
use RuntimeException;
use InvalidArgumentException;
use Twipsi\Components\Security\Exceptions\UnknownAlgorithmException;

class Hasher
{
    /**
     * Hash data using BCRYPT algorithm.
     * 
     * @param string $data
     * @param int $rounds
     * @return string
     * @throws UnknownAlgorithmException
     */
    public static function hashBcrypt(string $data, int $rounds = 10): string
    {
        try {
            return password_hash($data, PASSWORD_BCRYPT, [
                'cost' => $rounds
            ]);

        } catch (Error) {
            throw new UnknownAlgorithmException("The requested algorithm Bcrypt is not supported");
        }
    }

    /**
     * Hash data using ARGON2I algorithm.
     * 
     * @param string $data
     * @param array $options
     * @return string
     * @throws UnknownAlgorithmException
     */
    public static function hashArgon(string $data, array $options = []): string
    {
        try {
            return password_hash($data, PASSWORD_ARGON2I, [
                'memory_cost' => $options['memory'] ?? 1024,
                'time_cost' => $options['cost'] ?? 2,
                'threads' => $options['threads'] ?? 1,
            ]);

        } catch (Error) {
            throw new UnknownAlgorithmException("The requested algorithm Argon is not supported");
        }
    }

    /**
     * Check if a value matches the hash.
     *
     * @param string $value
     * @param string $hash
     * @param string|null $method [bcrypt, argon2i]
     * @return bool
     */
    public static function verifyPassword(string $value, string $hash, string $method = null): bool
    {
        if(!is_null($method) && static::hashInfo($hash)['algoName'] !== $method) {
            throw new RuntimeException(sprintf("The provided hash isn't using the [%s] algorithm", $method));
        }

        return password_verify($value, $hash);
    }

    /**
     * Get information about the hash.
     * 
     * @param string $hash
     * @return array
     */
    public static function hashInfo(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * Create a fast hash (don't use for security purposes).
     * 
     * @param string $data
     * @return string
     */
    public static function hashFast(string $data): string
    {
        return sha1($data);
    }

    /**
     * Create a random hash using a key.
     * 
     * @param string $key
     * @param string $method
     * @return string
     */
    public static function hashRandom(string $key, string $method = 'sha256'): string
    {
        return hash_hmac($method, KeyGenerator::generateSecureKey(40), $key);
    }

    /**
     * Create a hash for a specific data using a key.
     * 
     * @param string $data
     * @param string $key
     * @param string $method
     * 
     * @return string
     */
    public static function hashData(string $data, string $key, string $method = 'sha256'): string
    {
        return hash_hmac($method, $data, $key);
    }

    /**
     * Check if a basic hash variation matches.
     * 
     * @param string $hash
     * @param string $token
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function checkHash(string $hash, string $token): bool
    {
        if (!empty($hash)) {
            return hash_equals($token, $hash);
        }

        throw new InvalidArgumentException('Provided hash/token pair can not be empty');
    }
}
