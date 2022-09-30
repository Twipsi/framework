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

namespace Twipsi\Components\Authentication\Exceptions;

use Exception;

final class AuthenticationException extends Exception
{
    /**
     * Construct Authentication Exception.
     * 
     * @param string $message
     * @param null|string $driver
     * @param null|string $redirectUrl
     */
    public function __construct(string $message = 'Unauthenticated.', public ?string $driver = null, public ?string $redirectUrl = null)
    {
        parent::__construct($message);
    }
}
