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

trait CompilesCollections
{
    /**
     * Compiles collection data that can be printed with @bag.
     *
     * @param string $collection
     * @return string
     */
    public function compilesCollect(string $collection): string
    {
        return "<?php \$__engine->startCollection('{$collection}'); ?>";
    }

    /**
     * Compiles end collection.
     *
     * @return string
     */
    public function compilesEndcollect(): string
    {
        return "<?php \$__engine->endCollection(); ?>";
    }

    /**
     * Compile prepend to a collection.
     *
     * @param string $collection
     * @return string
     */
    public function compilesPrepend(string $collection): string
    {
        return "<?php \$__engine->startPrependToCollection('{$collection}'); ?>";
    }

    /**
     * Compile end prepend.
     *
     * @return string
     */
    public function compilesEndprepend(): string
    {
        return "<?php \$__engine->endPrependToCollection(); ?>";
    }

    /**
     * Compile bag renders a collection.
     *
     * @param string $collection
     * @return string
     */
    public function compilesBag(string $collection): string
    {
        return "<?php echo \$__engine->yieldCollection('{$collection}'); ?>";
    }
}
