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

use Twipsi\Support\Str;
use Twipsi\Components\View\ViewCompiler;
use Twipsi\Components\View\Compiler\CompilesHelpers;
use Twipsi\Components\View\Compiler\CompilesLayouts;
use Twipsi\Components\View\Compiler\CompilesTranslations;
use Twipsi\Components\View\Compiler\CompilesConditionals;
use Twipsi\Components\View\Compiler\CompilesCollections;
use Twipsi\Components\View\Compiler\CompilesVariables;
use Twipsi\Components\View\Compiler\Interfaces\CompilerInterface;

class StatementCompiler implements CompilerInterface
{
    use CompilesHelpers,
        CompilesLayouts,
        CompilesErrors,
        CompilesTranslations,
        CompilesConditionals,
        CompilesCollections,
        CompilesVariables;

    /**
     * The default extraction pattern.
     */
    protected const REGEX = "/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x";

    /**
     * Compiler constructor.
     */
    public function __construct(protected ViewCompiler $compiler)
    {
    }

    /**
     * Compile statements.
     */
    public function compile(string $content): string
    {
        return $this->convertToPHPData($content);
    }

    /**
     * Parse through the content and attempt to find statement identifiers.
     */
    protected function convertToPHPData(string $content): string
    {
        return preg_replace_callback(
            self::REGEX,
            function ($match) {
                return $this->processToken($match);
            },
            $content
        );
    }

    /**
     * Attempt to process the token and replace it.
     */
    protected function processToken(array $token): string
    {
        // If we have a double @ that means we are printing that
        // data converted to a single @.
        if (Str::hay($token[1])->first("@")) {
            return $token[1];
        }

        return $this->callCompiler($token) ?? $token[0];
    }

    /**
     * Loop through the compilers to process data.
     */
    protected function callCompiler(array $token): ?string
    {
        // Attempt to find the right class to method the statement.
        if (method_exists($this, $method = 'compiles' . ucfirst($token[1]))) {

            return $this->{$method}(isset($token[3]) ? $this->formatParanthesis($token[3]) : "");
        }

        return null;
    }

    /**
     * Format the parameters or expression in brackets.
     */
    protected function formatParanthesis(string $token): ?string
    {
        $formated = Str::hay($token)->pull(1, -1);

        // We are requesting a facade.
        if (Str::hay($formated)->resembles("::")) {
            $formated = $this->prependNamespace($formated);
        }

        return $formated;
    }

    /**
     * Append namespace if we have a facade.
     */
    protected function prependNamespace(string $class): string
    {
        return "\Twipsi\Facades\\" . $class;
    }
}
