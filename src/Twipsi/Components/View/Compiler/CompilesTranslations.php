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

trait CompilesTranslations
{
    /**
     * Compile csrf statement.
     */
    public function compilesLang(array $override): string
    {
        return "<?php \$__engine->startTranslation({$override}); ?>";
    }

    /**
     * Compile dump statement.
     */
    public function compilesEndlang(): string
    {
        return "<?php echo \$__engine->endTranslation(); ?>";
    }
}
