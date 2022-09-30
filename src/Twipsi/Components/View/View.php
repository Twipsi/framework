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

use \Throwable;
use \Closure;
use Twipsi\Components\File\FileItem;
use Twipsi\Components\View\ViewEngine;
use Twipsi\Components\View\Extractors\Interfaces\ViewExtractorInterface as Extractor;
use Twipsi\Components\Mailer\MessageBag;

class View
{
    /**
     * View engine constructor
     */
    public function __construct(
        protected ViewEngine $engine,
        protected Extractor $extractor,
        protected string $view,
        protected FileItem $file,
        protected array $data
    ) {
    }

    /**
     * Initiate render sequence.
     */
    public function render(Closure $closure = null): string|array
    {
        try {
            $content = $this->renderContent();

            if (!is_null($closure)) {
                $content = $closure($this, $content) ?? $content;
            }

            $this->engine->flushEngineOnComplete();

            return $content;
        } catch (Throwable $e) {
            $this->engine->flushEngine();

            throw $e;
        }
    }

    /**
     * Render the complete view file.
     */
    protected function renderContent(): string
    {
        // Keep track of all the renders done.
        $this->engine->increment();

        $content = $this->getDataFromExtractor();

        // If completed decrement render number.
        $this->engine->decrement();

        return $content;
    }

    /**
     * Queue some data before rendering the view.
     */
    public function queue(string|array $keys, mixed $value = null): View
    {
        $this->data = is_array($keys)
            ? array_merge($this->data, $keys)
            : ($this->data[$keys] = $value);

        return $this;
    }

    /**
     * Queue some errors before rendering the view.
     */
    public function error(MessageBag|array $message): View
    {
        $bag =
            $message instanceof MessageBag
                ? $message
                : new MessageBag($message);

        $this->queue("errors", new ViewErrorBag($bag));

        return $this;
    }

    /**
     * Render only the sections.
     */
    public function renderSections(): array
    {
        return $this->render(fn() => $this->engine->getSections());
    }

    /**
     * Get the data from the view file and compile it.
     */
    protected function getDataFromExtractor(): string
    {
        return $this->extractor->getContent($this->file, $this->collectData());
    }

    /**
     * Return the data that should be shared with the view.
     */
    protected function collectData(): array
    {
        return array_merge($this->data, $this->engine->factory()->getQueued());
    }

    /**
     * Get view name.
     */
    public function name(): string
    {
        return $this->view;
    }

    /**
     * Get view data.
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get view file.
     */
    public function file(): FileItem
    {
        return $this->file;
    }

    /**
     * Get view path.
     */
    public function path(): string
    {
        return $this->file->getpath();
    }
}
