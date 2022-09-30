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

namespace Twipsi\Components\User\Interfaces;

interface INotifiable
{
    /**
     * Get the To address based on the driver.
     * 
     * @param string $driver
     * @return mixed
     */
    public function recipients(string $driver): mixed;
}
