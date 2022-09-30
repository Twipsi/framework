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

namespace Twipsi\Components\Translator;

use InvalidArgumentException;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Support\Str;

class LocaleLocator
{
    /**
     * Found locale container.
     */
    protected array $found;

    /**
     * language finder constructor
     */
    public function __construct(protected string $path) {}

    /**
     * Attempt to find the location of a language.
     */
    public function locate(string $locale, string $group): ?FileItem
    {
        // If we have already located it return it.
        if (isset($this->found[$locale.'.'.$group])) {
            return $this->found[$locale.'.'.$group];
        }

        if (!is_null($file = $this->attemptToFindFile($locale, $group, $this->path))) {
            return $this->found[$locale.'.'.$group] = $file;
        }

        return null;
    }

    /**
     * Attempt to find the location of a locale file.
     */
    public function attemptToFindFile(string $locale, string $group, string $path): ?FileItem
    {
        $directory = $this->buildBaseLocaleDirectory($path);

        try {
            return new FileItem($directory .'/'. $locale .'/'. $group .'.php');
        } catch (FileNotFoundException) {
            return null;
        }
    }

     /**
     * Normalize the directory path.
     */
    protected function buildBaseLocaleDirectory(string $path): string
    {
        if (is_dir($fullPath = trim($path, "/"))) {
            return $fullPath;
        }

        throw new InvalidArgumentException(
            sprintf("Directory [%s] could not be found.", $path)
        );
    }
}