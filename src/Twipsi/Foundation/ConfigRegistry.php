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
use Twipsi\Support\Str;

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
     * Push a parameter to configuration section.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $recursive
     * @return static
     */
    public function push(string $key, mixed $value, bool $recursive = true): static
    {
        $section = $this->get($key);

        if (!is_null($section)) {

            $section = $section->merge($value);
            $this->set($key, $section, $recursive);
        }

        return $this;
    }

    /**
     * Get value of an an entry in a configuration section.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (Str::hay($key)->contains('.')) {

            $sections = explode('.', $key);

            $entry = array_reduce($sections, function ($carry, $item) {
                if (!$carry) {
                    return parent::get($item);
                }

                return $carry[$item] ?? null;
            });

            if (!is_null($entry)) {
                return is_array($entry) ? new self($entry) : $entry;
            }

            return $default;
        }

        return is_array($values = parent::get($key, $default)) ? new self($values) : $values;
    }

}
