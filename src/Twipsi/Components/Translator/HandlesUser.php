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

namespace Twipsi\Components\Translator;

use Closure;
use Twipsi\Bridge\User\ModelUser;

trait HandlesUser
{
    /**
     * The authenticated user model.
     * 
     * @var Closure
     */
    protected Closure $user;

    /**
     * Set the current authenticated user to the translator.
     * 
     * @param Closure $userLoader
     * 
     * @return void
     */
    public function attachUserToTranslator(Closure $userLoader): void 
    {
        $this->user = $userLoader;
    }

    /**
     * Load the current user into the translator.
     * 
     * @return ModelUser|null
     */
    protected function loadUser(): ?ModelUser
    {
        if(!is_null($this->user)) {
            $user = call_user_func($this->user);
        }

        if($user instanceof ModelUser) {
            return $user;
        }

        return null;
    }
}