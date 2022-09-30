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

use Twipsi\Support\KeyGenerator;
use Twipsi\Components\View\ViewFactory;
use Twipsi\Components\View\Engine\HandlesSections;
use Twipsi\Components\View\Engine\HandlesTranslations;
use Twipsi\Components\View\Engine\HandlesCollections;

class ViewEngine
{
    use HandlesSections,
    HandlesTranslations,
    HandlesCollections;

    /**
     * Render count.
     */
    public int $renders = 0;

    /**
     * Salt to use for identifiers.
     */
    protected string $salt;

    /**
     * Container to store placeholders.
     */
    protected array $placeholders = [];

    /**
     * Container to store collection rendering stacks.
     */
    protected array $collectionStack = [];

    /**
     * Container to store collections.
     */
    protected array $collections = [];

    /**
     * Enviroment constructor.
     */
    public function __construct(protected ViewFactory $factory)
    {
        $this->salt = sha1(KeyGenerator::generateAlphanumeric(40));
    }

    /**
     * Return the view factory.
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    /**
     * Flush all the engine data.
     */
    public function flushEngine(): void
    {
        $this->flushSections();
    }

    /**
     * Flush all the engine if rendering is done.
     */
    public function flushEngineOnComplete(): void
    {
        if($this->renders == 0) {
          $this->flushEngine();
        }
    }

    /**
     * Return the view factory.
     */
    public function factory(): ViewFactory
    {
        return $this->factory;
    }

    /**
     * Increment render count.
     */
    public function increment(): void
    {
        $this->renders++;
    }

    /**
     * Decrement render count.
     */
    public function decrement(): void
    {
        $this->renders--;
    }
}
