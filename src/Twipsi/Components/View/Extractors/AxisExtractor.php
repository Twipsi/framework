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

namespace Twipsi\Components\View\Extractors;

use \Throwable;
use Twipsi\Components\File\FileItem;
use Twipsi\Components\View\ViewCompiler;
use Twipsi\Components\View\ViewCache;
use Twipsi\Components\View\Extractors\PhpExtractor;
use Twipsi\Components\View\Extractors\Interfaces\ViewExtractorInterface;
use Twipsi\Components\View\Exceptions\ViewException;

class AxisExtractor extends PhpExtractor implements ViewExtractorInterface
{
    /**
     * The current path compiled.
     */
    protected string $currentPath;

    /**
     * Axis extractor constructor.
     */
    public function __construct(protected ViewCache $cache) {}

    /**
     * Return the content of the file found at path.
     */
    public function getContent(FileItem $file, array $data): ?string
    {
        $this->currentPath = $file->getPath();

        // Attempt to cache the file.
        if ($this->cache->expired($file) && $this->cache->usesCache()) {
            $this->cache->store($file, (new ViewCompiler)->compile($file)
            );
        }

        return parent::getContent(
            $this->cache->usesCache()
                ? $this->cache->getCompiledViewFile($file)
                : $file,
            $data
        );
    }

    /**
     * Return the content of the file found at path.
     */
    public function handleException(Throwable $e, int $obLevel): void
    {
        $msg = $e->getMessage() . " in View: " . $this->currentPath;

        $exception = new ViewException($msg, 0, 1, $e->getFile(), $e->getLine(), $e);
        parent::handleException($exception, $obLevel);
    }
}
