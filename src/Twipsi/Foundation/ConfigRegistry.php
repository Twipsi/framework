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

namespace Twipsi\Foundation;

use Twipsi\Support\Bags\ArrayAccessibleBag;

class ConfigRegistry extends ArrayAccessibleBag
{
    /**
     * Config bag constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs = [])
    {
        parent::__construct($configs);
    }

    /**
     * Get value of an entry in a configuration section.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return is_array($values = parent::get($key, $default)) ? new self($values) : $values;
    }
}
