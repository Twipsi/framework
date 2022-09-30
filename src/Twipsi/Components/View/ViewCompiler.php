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

use Twipsi\Components\File\FileItem;
use Twipsi\Support\Str;

class ViewCompiler
{
    /**
     * The opening php token identifier.
     */
    protected const PHP_OPEN_TOKEN = "@php";

    /**
     * The closing php token identifier.
     */
    protected const PHP_CLOSE_TOKEN = "@endphp";

    /**
     * The file containg the view.
     */
    protected FileItem $view;

    /**
     * The compilers we should initiate.
     */
    protected array $compilers = [
        "Statement",
        'Echo',
    ];

    /**
     * Container to hold raw php code.
     */
    protected array $phpBlocks;

    /**
     * Container to sotre footer data to be appended.
     */
    protected array $footers;

    /**
     * View compiler constructor
     */
    public function __construct(){}

    /**
     * Compile content of a view.
     */
    public function compile(FileItem $file): string
    {
        $this->setView($file);

        // Compile the view data.
        $content = $this->compileView($this->view->getContent());

        // Add file path to view footer.
        return $content."\n"."<?php /**PATH {".$this->getPath()."} ENDPATH**/ ?>";
    }

    /**
     * Compile the final view content to its final form.
     */
    public function compileView(string $content): string
    {
        // Extract all raw php data before compiling.
        $content = $this->parsePhpTokens($content);

        // Parse the php tokens
        foreach (token_get_all($content) as $token) {
            $compiled[] =
                is_array($token) && $token[0] === T_INLINE_HTML
                    ? $this->parseViewTokens($token[1])
                    : (isset($token[1]) ? $token[1] : '');
        }

        $content = implode("", $compiled ?? []);

        // Restore raw php data after compiling.
        if (!empty($this->phpBlocks)) {
            $content = $this->restorePhpBlocks($content);
        }

        // Add all the footers to the view.
        if (!empty($this->footers)) {
            $content = $this->prependFooters($content);
        }

        // Flush the data in the compiler.
        $this->flushCompiler();

        return $content;
    }

    /**
     * Parse the data and compile all parts of it.
     */
    protected function parseViewTokens(string $content): string
    {
        foreach ($this->compilers as $compiler) {
            $compiler =
                "Twipsi\Components\View\Compiler\\" .
                ucfirst($compiler) .
                "Compiler";
            $content = (new $compiler($this))->compile($content);
        }

        return $content;
    }

    /**
     * Parse the data and compile all parts of it.
     */
    protected function parsePhpTokens(string $content): ?string
    {
        $codes = Str::hay($content)->between(
            self::PHP_OPEN_TOKEN,
            self::PHP_CLOSE_TOKEN
        );

        foreach ($codes ?? [] as $code) {
            $content = Str::hay($content)->replace(
                self::PHP_OPEN_TOKEN . $code . self::PHP_CLOSE_TOKEN,
                $this->buildPhpBlockIdentifier($this->storePhpBlock($code))
            );
        }

        return $content;
    }

    /**
     * Restore all the php blocks.
     */
    protected function restorePhpBlocks(string $content): string
    {
        foreach ($this->phpBlocks as $key => $block) {
            $content = Str::hay($content)->replace(
                $this->buildPhpBlockIdentifier($key),
                $block
            );
        }

        return $content;
    }

    /**
     * Save the php blocks to be able to restore later.
     */
    protected function storePhpBlock(string $code): int
    {
        $this->phpBlocks[] = "<?php" . $code . "?>";

        return array_key_last($this->phpBlocks);
    }

    /**
     * Return a block identifier token.
     */
    protected function buildPhpBlockIdentifier(int $key): string
    {
        return '__phpblock_' . $key . '__';
    }

    /**
     * Prepend footer data to the view.
     */
    protected function prependFooters(string $content): string
    {
        return $content . "\n" . implode("\n", $this->footers);
    }

    /**
     * Prepend footer data to the view.
     */
    public function addFooter(string $footer): void
    {
        $this->footers[] = $footer;
    }

    /**
     * Flush compiler data.
     */
    public function flushCompiler(): void
    {
        $this->footers = [];
        $this->phpBlocks = [];
    }

    /**
     * Set the current view file.
     */
    public function setView(FileItem $file): void
    {
        $this->view = $file;
    }

    /**
     * Get the current view path.
     */
    public function getPath(): string
    {
        return $this->view->getPath();
    }
}
