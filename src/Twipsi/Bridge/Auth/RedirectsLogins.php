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

use Twipsi\Facades\Url;

trait RedirectsLogins
{
    /**
     * Get the path where we should redirect to;
     * 
     * @return string
     */
    protected function redirectPath(): string 
    {
        if(property_exists($this, 'redirectTo')) {
            return $this->redirectTo;
        }

        return method_exists($this, 'redirectTo')
            ? $this->redirectTo()
            : $this->redirectFallback();
    }

    /**
     * Get the redirect fallback in case we don't have any set.
     * 
     * @return string
     */
    protected function redirectFallback(): string 
    {
        return Url::route('home') ?? '/';
    }
}