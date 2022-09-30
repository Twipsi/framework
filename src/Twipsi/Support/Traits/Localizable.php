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

namespace Twipsi\Support\Traits;

use \Closure;
use Twipsi\Facades\App;

trait Localizable
{
    /**
     * Run a closure with a different locale and then revert.
     * 
     * @param string $locale
     * @param Closure $callback
     * 
     * @return mixed
     */
    public function withLocale(?string $locale, Closure $callback): mixed
    {
        if(is_null($locale)) {
            return $callback();
        }
        
        $saved = App::getLocale();

        try{
            App::setLocale($locale);
            return $callback();

        } finally {
            App::setLocale($saved);
        }
    }
}