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

namespace Twipsi\Components\View;

use Twipsi\Support\Bags\ArrayBag as Container;
use Twipsi\Components\Mailer\MessageBag;

class ViewErrorBag extends Container
{
    /**
     * Constructor
     */
    public function __construct(null|array|MessageBag $messages = null, ?ViewErrorBag $errors = null) 
    {
        if(is_array($messages)) {
            $messages = new MessageBag($messages);
        }
        
        if (!is_null($messages) && !is_null($errors)) {
            $messages = $messages->merge($errors->all());
        }

        parent::__construct($messages?->all() ?? []);
    }
}
