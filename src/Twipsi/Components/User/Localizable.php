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

namespace Twipsi\Components\User;

trait Localizable
{
    /**
     * Name of the column that stores the locale.
     * 
     * @var string
     */
    protected string $LOCALECOLUMN = 'locale';

    /**
     * Set the model locale.
     * 
     * @param string $locale
     * @return self
     */
    public function setLocale(string $locale): self
    {
        $this->LOCALECOLUMN = $locale;

        return $this;
    }

    /**
     * Get the model locale.
     * 
     * @return string
     */
    public function getLocale(): string 
    {
        return $this->LOCALECOLUMN;
    }
}
