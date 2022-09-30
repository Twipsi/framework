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

use Twipsi\Components\Cookie\CookieBag;

trait HandlesCookies
{
    /**
     * $_COOKIE data.
     *
     * @var CookieBag
     */
    protected CookieBag $cookies;

    /**
     * Set the cookie bag.
     *
     * @param array $cookies
     *
     * @return void
     */
    public function setCookies(array $cookies): void
    {
        $this->cookies = new CookieBag($cookies);
    }

    /**
     * Return the cookie bag.
     *
     * @return CookieBag
     */
    public function cookies(): CookieBag
    {
        return $this->cookies;
    }
}
