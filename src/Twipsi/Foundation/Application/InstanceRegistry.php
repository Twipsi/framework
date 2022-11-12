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

use Twipsi\Support\Bags\SimpleBag as Container;

class InstanceRegistry extends Container
{
    /**
     * Instance registry constructor.
     *
     * @param array $instances
     */
    public function __construct(array $instances = [])
    {
        parent::__construct($instances);
    }

    /**
     * Bind an instance to an abstract.
     *
     * @param string $abstract
     * @param object $instance
     * @return void
     */
    public function bind(string $abstract, object $instance): void
    {
        $this->set($abstract, $instance);
    }
}
