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

namespace Twipsi\Components\View\Compiler;

trait CompilesHelpers
{
    /**
     * Compile csrf statement.
     */
    public function compilesCsrf(): string
    {
        return '<?php echo \'<input type="hidden" name="csrf_token" value="\'.csrf_token().\'">\'; ?>';
    }

    /**
     * Compile dump statement.
     */
    public function compilesDump(string $content): string
    {
        return "<?php echo dump($content) ?>";
    }
}
