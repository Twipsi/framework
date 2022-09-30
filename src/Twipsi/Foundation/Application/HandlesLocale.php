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

namespace Twipsi\Foundation\Application;

trait HandlesLocale
{
    /**
     * Get the current locale.
     * 
     * @return string
     */
    public function getLocale(): string 
    {
        return $this->get('config')->get('system.locale');
    }

    /**
     * Check if current locale is locale.
     * 
     * @param string $locale
     * 
     * @return bool
     */
    public function isLocale(string $locale): bool 
    {
        return $this->getLocale() === $locale;
    }

    /**
     * Set the current locale.
     * 
     * @param string $locale
     * 
     * @return void
     */
    public function setLocale(string $locale): void 
    {
        $this->get('config')->set('system.locale', $locale);
        $this->get('translator')->setLocale($locale);
    }
}