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

namespace Twipsi\Support\Applicables;

use Twipsi\Components\Session\SessionItem;

trait Session
{
    protected SessionItem $session;

    /**
     * Set the session instance
     */
    public function appendSession(SessionItem $session): static
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get the session instance
     */
    public function getSession()
    {
        return $this->session;
    }

}
