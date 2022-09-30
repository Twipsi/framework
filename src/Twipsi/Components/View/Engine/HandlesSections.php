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

trait HandlesSections
{
    /**
     * Container to store section rendering stacks.
     */
    protected array $sectionStack = [];

    /**
     * Container to store sections.
     */
    protected array $sections = [];

    /**
     * Start the section.
     */
    public function startSection(string $section): void
    {
        if (ob_start()) {
            $this->sectionStack[] = $section;
        }
    }

    /**
     * End the section.
     */
    public function endSection(bool $strict = false): string
    {
        if (empty($this->sectionStack)) {
            throw new RuntimeException("Start section needs to be called before End section");
        }

        $section = array_pop($this->sectionStack);

        $strict
            ? ($this->sections[$section] = ob_get_clean())
            : $this->fillSection($section, ob_get_clean());

        return $section;
    }

    /**
     * Store the section content adding to current section (too extend same section).
     */
    public function appendSection(): string
    {
        if (empty($this->sectionStack)) {
            throw new RuntimeException("Start section needs to be called before End section");
        }

        $section = array_pop($this->sectionStack);

        if (isset($this->sections[$section])) {
            $this->sections[$section] .= ob_get_clean();
        } else {
            $this->sections[$section] = ob_get_clean();
        }

        return $section;
    }

    /**
     * Store the section content in its placeholder (too extend same section).
     */
    public function fillSection(?string $section, string $content): void
    {
        if (isset($this->sections[$section])) {
            $content = Str::hay($this->sections[$section])->replace(
                $this->getPlaceholder($section),
                $content
            );
        }

        $this->sections[$section] = $content;
    }

    /**
     * Yield the section contents while ending it.
     */
    public function yieldSection(): string
    {
        if (empty($this->sectionStack)) {
            return "";
        }

        return $this->yieldContent($this->endSection());
    }

    /**
     * Yield the section contents stored with a default fallback.
     */
    public function yieldContent(string $section, string $default = ""): string
    {
        $content = $default;

        if (isset($this->sections[$section])) {
            $content = $this->sections[$section];
        }

        return str_replace($this->getPlaceholder($section), '', $content);
    }

    /**
     * Return the view factory.
     */
    public function getPlaceholder(string $section): string
    {
        if (!isset($this->placeholders[$section])) {
            $this->placeholders[$section] =
                "__placeholder__" . $this->salt . "__";
        }

        return $this->placeholders[$section];
    }

    /**
     * Return the sections.
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function flushSections(): void 
    {
        $this->sections = [];
        $this->sectionStack = [];
    }
}
