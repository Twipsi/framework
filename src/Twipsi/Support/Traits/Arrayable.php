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

use ReflectionObject;
use ReflectionProperty;

trait Arrayable
{
    /**
     * Return all the public properties as named array.
     * 
     * @param bool $strict - only get base class properties
     * 
     * @return array
     */
    public function toArray(bool $strict = false): array
    {
        $class = new ReflectionObject($this);

        foreach($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {

            if($strict && $property->getDeclaringClass()->getName() 
                    != $class->getParentClass()->getName()) {

                continue;
            }

            if(! $property->isInitialized($this)) {
                $array[$property->getName()] = null;
                continue;
            }

            $array[$property->getName()] = $property->getValue($this);
        }

        return $array ?? [];
    }

    /**
     * Return all the public properties as named array 
     * when called as an array.
     * 
     * @return array
     */
    public function __toArray(): array 
    {
        return $this->toArray();
    }
}