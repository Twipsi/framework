<?php

namespace Twipsi\Components\Router\Route;

use Opis\Closure\SerializableClosure;

trait Serializable
{
    /**
     * Called when an object gets serialized.
     *
     * @return array
     */
    public function __sleep() : array
    {
        $properties = (new \ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {

            if($property->isInitialized($this)
                    && ($data = $property->getValue($this)) instanceof \Closure) {

                $method = 'set'.ucfirst($property->getName());
                $this->{$method}(new SerializableClosure($data));
            }

            if(! is_object($data ?? null)) {
                $saved[] = $property->getName();
            }

        }

        return $saved ?? [];
    }

    /**
     * Restore the object with additional logics.
     */
    public function __wakeup() : void
    {
        $this->callback = isset($this->callback) && $this->callback instanceof SerializableClosure
            ? $this->callback->getClosure() : $this->callback ?? null;

        $this->fallback = isset($this->fallback) && $this->fallback instanceof SerializableClosure
            ? $this->fallback instanceof SerializableClosure : null;
    }
}