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
use Twipsi\Components\View\Extractors\Interfaces\ViewExtractorInterface;

class PhpExtractor implements ViewExtractorInterface
{
    /**
     * Return the content of the file found at path.
     */
    public function getContent(FileItem $file, array $data): ?string
    {
        // Start memory buffer to collect the computed response.
        $obLevel = ob_get_level();
        ob_start(null, 0, PHP_OUTPUT_HANDLER_REMOVABLE);

        try {
            $file->extractContent($data);
        } catch (Throwable $e) {
            $this->handleException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * Return the content of the file found at path.
     */
    public function handleException(Throwable $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
