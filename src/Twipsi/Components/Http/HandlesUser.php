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

namespace Twipsi\Components\Http;

use Closure;
use Twipsi\Bridge\User\ModelUser;

trait HandlesUser
{
    /**
     * User loader.
     *
     * @var Closure
     */
    protected Closure $user;

    /**
     * Attach the user laoder to request object.
     *
     * @param Closure $userLoader
     *
     * @return void
     */
    public function attachUser(Closure $userLoader): void
    {
        $this->user = $userLoader;
    }

    /**
     * Get the user for a specified driver.
     *
     * @param string|null $driver
     *
     * @return [type]
     */
    public function user(string $driver = null): ?ModelUser
    {
        return !is_null($this->user)
            ? call_user_func($this->user, $driver)
            : null;
    }
}
