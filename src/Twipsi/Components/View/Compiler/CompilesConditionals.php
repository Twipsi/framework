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

trait CompilesConditionals
{
    /**
     * Compile auth statement.
     */
    public function compilesAuth(string $driver): string
    {
        return "<?php if(auth()->driver({$driver})->check()): ?>";
    }

    /**
     * Compile esle auth statement.
     */
    public function compilesElseauth(string $driver): string
    {
        return "<?php elseif(auth()->driver({$driver})->check()): ?>";
    }

    /**
     * Compile auth end statement.
     */
    public function compilesEndauth(): string
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile access statement.
     */
    public function compilesAccess(string $policy): string
    {
        return "<?php if(access({$policy})): ?>";
    }

    /**
     * Compile esle access statement.
     */
    public function compilesElseaccess(string $policy): string
    {
        return "<?php elseif(access({$policy})): ?>";
    }

    /**
     * Compile access end statement.
     */
    public function compilesEndaccess(): string
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile guest statement.
     */
    public function compilesGuest(string $driver): string
    {
        return "<?php if(auth()->driver({$driver})->guest()): ?>";
    }

    /**
     * Compile else guest statement.
     */
    public function compilesElseGuest(string $driver): string
    {
        return "<?php elseif(auth()->driver({$driver})->guest()): ?>";
    }

    /**
     * Compile guest end statement.
     */
    public function compilesEndguest(): string
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile if statement.
     */
    public function compilesIf(string $parameter): string
    {
        return "<?php if({$parameter}): ?>";
    }

    /**
     * Compile unless statement.
     */
    public function compilesUnless(string $parameter): string
    {
        return "<?php if(! {$parameter}): ?>";
    }

    /**
     * Compile else if statement.
     */
    public function compilesElseif(string $parameter): string
    {
        return "<?php elseif({$parameter}): ?>";
    }

    /**
     * Compile else statement.
     */
    public function compilesElse(): string
    {
        return "<?php else: ?>";
    }

    /**
     * Compile endif statement.
     */
    public function compilesEndif(): string
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile endunless statement.
     */
    public function compilesEndunless(): string
    {
        return "<?php endif; ?>";
    }
}
