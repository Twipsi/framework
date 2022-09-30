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

use Twipsi\Components\Session\SessionItem;
use Twipsi\Components\Session\EncryptedSessionItemItem;

trait HandlesSession
{
    /**
     * The session object.
     *
     * @var SessionItem|EncryptedSessionItemItem
     */
    protected SessionItem|EncryptedSessionItemItem $session;

    /**
     * Set the session itme to request.
     *
     * @param SessionItem|EncryptedSessionItemItem $session
     *
     * @return void
     */
    public function setSession(SessionItem|EncryptedSessionItemItem $session): void
    {
        $this->session = $session;
    }

    /**
     * Check if we have a session.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return !is_null($this->session);
    }

    /**
     * Get the request session.
     *
     * @return mixed
     */
    public function session(): mixed
    {
        return $this->session;
    }
}
