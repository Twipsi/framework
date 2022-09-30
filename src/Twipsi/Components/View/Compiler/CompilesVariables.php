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

trait CompilesVariables
{
    /**
     * Compiles inject to add data to variables and pass to the view.
     *
     * @param string $parameters
     * @return string
     */
    public function compilesInject(string $parameters): string
    {
        [$variable, $component] = explode(",", $parameters);

        $variable = trim($variable, " '");
        return '<?php $' . $variable .' = $__app->get('.trim($component).'); ?>';
    }

    /**
     * Compiles var to add data to variables and pass to the view.
     *
     * @param string $parameters
     * @return string
     */
    public function compilesVar(string $parameters): string
    {
        [$variable, $value] = explode(",", $parameters);

        $variable = trim($variable, " '");
        return '<?php $' . $variable . " = " . trim($value) . "; ?>";
    }
}
