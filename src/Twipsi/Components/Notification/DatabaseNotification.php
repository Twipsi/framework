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

namespace Twipsi\Components\Notification;

use Twipsi\Components\Model\Model;

class DatabaseNotification extends Model
{
    /**
     * The db table to save to.
     * 
     * @var string
     */
    public string $table = 'notifications';


    
}