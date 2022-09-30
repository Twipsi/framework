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

trait CompilesErrors
{
    /**
     * Compile csrf statement.
     */
    public function compilesError(string $content): string
    {
        return '<?php 
        if($errors->has('.$content.')) : 
            if(isset($message)) { $__original_message = $message; }
            $message = $errors->get('.$content .');
      ?>';
    }

    /**
     * Compile dump statement.
     */
    public function compilesEnderror(): string
    {
        return '<?php unset($message);
            if(isset($__original_message)) { $message = $__original_message; }
            endif;
      ?>';
    }
}
