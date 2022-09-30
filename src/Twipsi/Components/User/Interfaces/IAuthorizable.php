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

namespace Twipsi\Components\User\Interfaces;

interface IAuthorizable
{
    /**
     * Check if user has access to an action in a module.
     *
     * @param string $action
     * @param string $where
     * @return bool
     */
    public function can(string $action, string $where): bool;

    /**
     * Check if user has access to any of the actions in the module.
     *
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function canAny(array $actions, string $where): bool;

    /**
     * Check if user has access to all the actions in the module.
     *
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function canAll(array $actions, string $where): bool;

    /**
     * Check if user has access to an action in a module.
     *
     * @param array|string $action
     * @param string $where
     * @return bool
     */
    public function cant(array|string $action, string $where): bool;

    /**
     * Check if user doesn't have access to any of the actions in the module.
     *
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function cantAny(array $actions, string $where): bool;

    /**
     * Check if user doesn't have access to all the actions in the module.
     *
     * @param array $actions
     * @param string $where
     * @return bool
     */
    public function cantAll(array $actions, string $where): bool;
}
