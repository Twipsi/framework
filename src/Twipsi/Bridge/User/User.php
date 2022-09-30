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

namespace Twipsi\Bridge\User;

use Twipsi\Components\User\Authenticatable;
use Twipsi\Components\User\Interfaces\IAuthenticatable;
use Twipsi\Support\Bags\ArrayBag as Container;

class User extends Container implements IAuthenticatable
{
    use Authenticatable;

    /**
     * Construct generic simple user.
     *
     * @param array $user
     */
    public function __construct(array $user)
    {
        parent::__construct($user);
    }
}
