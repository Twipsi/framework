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

trait CompilesLayouts
{
    /**
     * Compiles extend statement.
     */
    public function compilesExtends(string $parameter): string
    {
        $this->compiler->addFooter(
            "<?php echo \$__engine->factory()->create('{$parameter}', get_defined_vars())->render(); ?>"
        );

        return "";
    }

    /**
     * Compiles component statement.
     */
    public function compilesComponent(string $parameter): string
    {
        return "<?php echo \$__engine->factory()->create('{$parameter}', get_defined_vars())->render(); ?>";
    }

    /**
     * Compiles section start statement.
     */
    public function compilesSection(string $parameter): string
    {
        return "<?php \$__engine->startSection('{$parameter}'); ?>";
    }

    /**
     * Compiles section end statement.
     */
    public function compilesEndsection(): string
    {
        return "<?php \$__engine->endSection(); ?>";
    }

    /**
     * Compiles append statement. @section() ... @append
     */
    public function compilesAppend(): string
    {
        return "<?php echo \$__engine->appendSection(); ?>";
    }

    /**
     * Compiles append statement. @section() ... @show
     */
    public function compilesShow(): string
    {
        return "<?php echo \$__engine->yieldSection(); ?>";
    }

    /**
     * Compiles overwrite statement. @section() ... @overwrite
     */
    public function compilesOverwrite(): string
    {
        return "<?php echo \$__engine->endSection(true); ?>";
    }

    /**
     * Compiles parent statement.
     */
    public function compilesParent(string $parameter): string
    {
        return "<?php echo \$__engine->getPlaceholder('{$parameter}'); ?>";
    }

    /**
     * Compiles yield statement.
     */
    public function compilesYield(string $parameter): string
    {
        return "<?php echo \$__engine->yieldContent('{$parameter}'); ?>";
    }
}
