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

use InvalidArgumentException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Components\File\FileItem;
use Twipsi\Components\View\ViewLocator;
use Twipsi\Components\View\ViewCache;
use Twipsi\Components\View\ViewEngine;

use Twipsi\Components\View\Extractors\AxisExtractor;
use Twipsi\Components\View\Extractors\PhpExtractor;
use Twipsi\Components\View\Extractors\FileExtractor;
use Twipsi\Components\View\Extractors\Interfaces\ViewExtractorInterface as ViewExtractor;

use Twipsi\Support\Str;
use Twipsi\Support\Arr;

class ViewFactory
{
    /**
     * Data that should be available for the view.
     */
    protected array $extensions = [
        "axis.php" => "axis",
        "php" => "php",
        "html" => "file",
        "css" => "file",
    ];

    /**
     * Data that can be prequeued for the View item.
     */
    protected array $queue;

    /**
     * View engine object.
     */
    protected ViewEngine $engine;

    /**
     * View factory constructor
     */
    public function __construct(protected ViewLocator $locator, protected ViewCache $cache, array $extensions = null)
    {
        if (!is_null($extensions)) {
            $this->extensions = $extensions;
        }

        $this->queue("__engine", $this->engine = new ViewEngine($this));
    }

    /**
     * Create a view based on a view.
     */
    public function create(string $view, array $data = []): View
    {
        $file = $this->locator->locate($view);

        return $this->buildView($view, $file, $data);
    }

    /**
     * Create a view based on a view.
     */
    public function exists(string $view): bool
    {
        try {
            $this->locator->locate($view);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Build the view instance.
     */
    protected function buildView(string $view, FileItem $file, array $data): View
    {
        return new View($this->engine, $this->getExtractor($file->getPath()), $view, $file, $data);
    }

    /**
     * Return the type of driver we will need.
     */
    protected function getExtractor(string $path): ViewExtractor
    {
        if (is_null($extension = $this->getViewExtension($path))) {
            throw new NotSupportedException(
                sprintf("Extension in path [%s] is not supported.", $path)
            );
        }

        return $this->resolveExtractor($this->extensions[$extension]);
    }

    /**
     * Return the type of driver we will need.
     */
    protected function getViewExtension(string $path): ?string
    {
        $parts = explode('/', $path);
        $extension = Str::hay(end($parts))->after(".");

        return in_array($extension, array_keys($this->extensions))
            ? $extension
            : null;
    }

    /**
     * Build the View extractor based on extension.
     */
    protected function resolveExtractor(string $extractor): ViewExtractor
    {
        switch ($extractor) {
            case "axis":
                return new AxisExtractor($this->cache);
            case "php":
                return new PhpExtractor();
            case "file":
                return new FileExtractor();
        }

        throw new NotSupportedException(
            sprintf("View Extractor [%s] is not supported.", $extractor)
        );
    }

    /**
     * Queue some data before initializing the view.
     */
    public function queue(string $key, mixed $value): void
    {
        $this->queue[$key] = $value;
    }

    /**
     * Queue some data before initializing the view.
     */
    public function dequeue(string $key): void
    {
        unset($this->queue[$key]);
    }

    /**
     * Get all Queued data.
     */
    public function getQueued(): array
    {
        return $this->queue;
    }
}
