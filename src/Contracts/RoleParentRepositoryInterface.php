<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Role;
use Vima\Core\Entities\RoleParent;

/**
 * Interface RoleParentRepositoryInterface
 * 
 * Defines the contract for managing role inheritance relationships in persistent storage.
 *
 * @package Vima\Core\Contracts
 */
interface RoleParentRepositoryInterface
{
    /**
     * Map a role to its parent relationship.
     *
     * @param RoleParent $relationship
     * @return void
     */
    public function assign(RoleParent $relationship): void;

    /**
     * Remove a role's inheritance from another role.
     *
     * @param RoleParent $relationship
     * @return void
     */
    public function remove(RoleParent $relationship): void;

    /**
     * Clear all parents for a role.
     *
     * @param Role $role
     * @return void
     */
    public function clearParents(Role $role): void;

    /**
     * Retrieve all parent IDs for a given role.
     *
     * @param Role $role
     * @return Role[]
     */
    public function getParents(Role $role): array;

    /**
     * Retrieve all child IDs for a given role.
     *
     * @param Role $role
     * @return Role[]
     */
    public function getChildren(Role $role): array;
}
