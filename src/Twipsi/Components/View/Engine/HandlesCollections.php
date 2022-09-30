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

namespace Twipsi\Components\View\Engine;

use RuntimeException;
use Twipsi\Support\Str;

trait HandlesCollections
{
    /**
     * Start the collection rendering.
     */
    public function startCollection(string $collection): void
    {
        if (ob_start()) {
            $this->collectionStack[] = $collection;
        }
    }

    /**
     * End the collection rendering.
     */
    public function endCollection(): void
    {
        if (empty($this->collectionStack)) {
            throw new RuntimeException("Start collection needs to be called before End collection");
        }

        $collection = array_pop($this->collectionStack);
        $this->appendCollection($collection, ob_get_clean());
    }

    /**
     * Store or extend data to a collection.
     */
    public function appendCollection(string $collection, string $content): void
    {
        if (! isset($this->collections[$collection])) {
            $this->collections[$collection] = $content;
        } else {
            $this->collections[$collection] .= $content;
        }
    }

    /**
     * Store data infront of the collection.
     */
    public function prependCollection(string $collection, string $content): void
    {
        if (! isset($this->collections[$collection])) {
            $this->collections[$collection] = $content;
        } else {
            $this->collections[$collection] = $content.$this->collections[$collection];
        }
    }

    /**
     * Start prepending to the collection.
     */
    public function startPrependToCollection(string $collection): void
    {
        if (ob_start()) {
            $this->collectionStack[] = $collection;
        }
    }

    /**
     * End prepending to the collection.
     */
    public function endPrependToCollection(): void
    {
        if (empty($this->collectionStack)) {
            throw new RuntimeException("Start collection needs to be called before End collection");
        }

        $collection = array_pop($this->collectionStack);
        $this->prependCollection($collection, ob_get_clean());
    }

    /**
     * Yield the collection contents stored with a default fallback.
     */
    public function yieldCollection(string $collection, string $default = ""): string
    {
        if (isset($this->collections[$collection])) {
            return $this->collections[$collection];
        }

        return $default;
    }
}
