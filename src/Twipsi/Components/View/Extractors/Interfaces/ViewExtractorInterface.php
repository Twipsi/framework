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

namespace Twipsi\Components\View\Extractors\Interfaces;

use \Throwable;
use Twipsi\Components\File\FileItem;

interface ViewExtractorInterface
{
    /**
     * Return the content of the file found at path.
     */
    public function getContent(FileItem $file, array $data): ?string;

    /**
     * Return the content of the file found at path.
     */
    public function handleException(Throwable $e, int $obLevel): void;
}
