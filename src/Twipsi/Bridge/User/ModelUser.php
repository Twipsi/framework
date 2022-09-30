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

use Twipsi\Components\Model\Model;
use Twipsi\Components\User\Authenticatable;
use Twipsi\Components\User\Authorizable;
use Twipsi\Components\User\Interfaces\IAuthenticatable;
use Twipsi\Components\User\Interfaces\IAuthorizable;
use Twipsi\Components\User\Interfaces\INotifiable;
use Twipsi\Components\User\Interfaces\IRefreshable;
use Twipsi\Components\User\Interfaces\IResetable;
use Twipsi\Components\User\Interfaces\IVerifiable;
use Twipsi\Components\User\Notifiable;
use Twipsi\Components\User\Refreshable;
use Twipsi\Components\User\Resetable;
use Twipsi\Components\User\Verifiable;

class ModelUser extends Model implements IAuthenticatable, IAuthorizable, IResetable,
    IVerifiable, IRefreshable, INotifiable
{
    use Authenticatable, Authorizable, Resetable,
        Verifiable, Refreshable, Notifiable;
}
