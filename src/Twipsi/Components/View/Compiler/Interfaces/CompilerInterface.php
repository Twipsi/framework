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

namespace Twipsi\Components\View\Compiler\Interfaces;

use Twipsi\Components\View\ViewCompiler;

interface CompilerInterface
{
    /**
     * Compiler constructor.
     */
    public function __construct(ViewCompiler $compiler);

    /**
     * Compile statements.
     */
    public function compile(string $parameter): string;
}
