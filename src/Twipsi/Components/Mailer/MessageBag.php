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

namespace Twipsi\Components\Mailer;

use Twipsi\Support\Bags\RecursiveArrayBag as Container;

class MessageBag extends Container
{
    /**
     * Constructor
     */
    public function __construct(array $messages = [])
    {
        parent::__construct($messages);
    }
}