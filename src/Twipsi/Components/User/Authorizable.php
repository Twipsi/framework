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

namespace Twipsi\Components\User;

use Twipsi\Facades\App;

trait Authorizable
{
    /**
     * Check if user has access to an action in a module.
     * 
     * @param string $action
     * @param string $where
     * @return bool
     */
    public function can(string $action, string $where): bool
    {
        return App::get("auth.rules")
            ->user($this)
            ->module($where)
            ->check($action);
    }

    /**
     * Check if user has access to any of the actions in the module.
     * 
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function canAny(array $actions, string $where): bool
    {
        return App::get("auth.rules")
            ->user($this)
            ->module($where)
            ->any($actions);
    }

    /**
     * Check if user has access to all the actions in the module.
     * 
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function canAll(array $actions, string $where): bool
    {
        return App::get("auth.rules")
            ->user($this)
            ->module($where)
            ->all($actions);
    }

    /**
     * Check if user has access to an action in a module.
     * 
     * @param array|string $action
     * @param string $where
     * @return bool
     */
    public function cant(array|string $action, string $where): bool
    {
        return !$this->can($action, $where);
    }

    /**
     * Check if user doesn't have access to any of the actions in the module.
     * 
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function cantAny(array $actions, string $where): bool
    {
        return !$this->canAny($actions, $where);
    }

    /**
     * Check if user doesn't have access to all the actions in the module.
     * 
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function cantAll(array $actions, string $where): bool
    {
        return !$this->canAll($actions, $where);
    }
}
