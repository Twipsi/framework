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

namespace Twipsi\Components\View\Engine;

use Twipsi\Facades\Translate;

trait HandlesTranslations
{
    protected array $translationOverrides;

    /**
     * Start a translation sequence.
     */
    public function startTranslation(array $override = []): void
    {
        if (ob_start()) {
            $this->translationOverrides = $override;
        }
    }

    /**
     * End the translation sequence.
     */
    public function endTranslation(): string
    {
      return Translate::translate(trim(ob_get_clean()), $this->translationOverrides);
    }
}