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
use Twipsi\Components\View\Compiler\Interfaces\CompilerInterface;

class EchoCompiler implements CompilerInterface
{
    /**
     * The default echo tags.
     */
    protected const ECHO_TAGS = ["{{", "}}"];

    /**
     * The escaped echo tags.
     */
    protected const ECHO_ESCAPE_TAGS = ["{{{", "}}}"];

    /**
     * The default extraction pattern.
     * [token => method => parameter]
     */
    protected const REGEX = "/\%s\s*(.*?)(\([^(}]+[$)]*)?\)?\s*\%s/s";


    /**
     * Compiler constructor.
     */
    public function __construct(protected ViewCompiler $compiler){}

    /**
     * Compile statements.
     */
    public function compile(string $content): string
    {
        return $this->compileStandardEchos(
            $this->compileEscapedEchos($content)
        );
    }

    /**
     * Parse through the content and attempt to find excaped echo identifiers.
     */
    protected function compileEscapedEchos(string $content): string
    {
        return preg_replace_callback(
            sprintf(self::REGEX, self::ECHO_ESCAPE_TAGS[0], self::ECHO_ESCAPE_TAGS[1]),
            function ($match) {
                return $this->processEscapedEchos($match);
            },
            $content
        );
    }

    /**
     * Parse through the content and attempt to find echo identifiers.
     */
    protected function compileStandardEchos(string $content): string
    {
        return preg_replace_callback(
            sprintf(self::REGEX, self::ECHO_TAGS[0], self::ECHO_TAGS[1]),
            function ($match) {
                return $this->processStandardEchos($match);
            },
            $content
        );
    }

    /**
     * Attempt to process the echo and replace it.
     */
    protected function processStandardEchos(array $token): string
    {
        $echo = $this->rebuildEchoFunctions($token);

        return "<?php echo {$echo}; ?>";
    }

    /**
     * Attempt to process the echo and replace it.
     */
    protected function processEscapedEchos(array $token): string
    {
        $echo = $this->rebuildEchoFunctions($token);

        return "<?php echo htmlentities({$echo}); ?>";
    }

    /**
     * Attempt to process the echo rebuild the function.
     */
    protected function rebuildEchoFunctions(array $token): string 
    {
        // if its not a method call its a variable.
        if(! isset($token[2]) && Str::hay($token[1])->first('$')) {
            return Str::hay($token[1])->remove(";");
        }

        // If we have a function in the echo.
        $echo = ! empty($token[2]) 
            ? $token[1].'('.implode(', ', $this->rebuildParameters($token[2])).')'
            : $token[1];
 
        // We are requesting a facade.
        if (Str::hay($echo)->resembles("::")) {
            $echo = $this->prependNamespace($echo);
        }

        return $echo;
    }

    /**
     * Extract the parameters between () and wrap them.
     */
    protected function rebuildParameters(string $parameters): array
    {
        // Check if there are any parameters.
        if(empty($params = Str::hay(trim($parameters))->pull(1, -1))) {
            return [];
        }

        foreach (explode(",", $params) as $param) {
            $param = trim($param, "' ");

            if ($this->isRealString($param)) {
                $param = Str::hay($param)->wrap("'");
            }

            // We are requesting a facade.
            if (Str::hay($param)->resembles("::")) {
                $param = $this->prependNamespace($param);
            }

            $result[] = $param;
        }
        return $result;
    }

    /**
     * Append namespace if we have a facade.
     */
    protected function prependNamespace(string $class): string
    {
        return "\Twipsi\Facades\\" . $class;
    }

    /**
     * Check if the string is really a string
     */
    protected function isRealString(string $param): bool
    {
        return ! Str::hay($param)->first('$') && ! Str::hay($param)->numeric() && ! Str::hay($param)->resembles("::") &&
            $param !== "true" && $param !== "false" && $param !== "null";
    }
}
