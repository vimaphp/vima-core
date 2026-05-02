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

use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BareRoleParent;

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
     * @param BareRoleParent $relationship
     * @return void
     */
    public function assign(BareRoleParent $relationship): void;

    /**
     * Remove a role's inheritance from another role.
     *
     * @param BareRoleParent $relationship
     * @return void
     */
    public function remove(BareRoleParent $relationship): void;

    /**
     * Clear all parents for a role.
     *
     * @param BareRole $role
     * @return void
     */
    public function clearParents(BareRole $role): void;

    /**
     * Retrieve all parent relationship records for a given role.
     *
     * @param BareRole $role
     * @return BareRoleParent[]
     */
    public function getParents(BareRole $role): array;

    /**
     * Retrieve all child relationship records for a given role.
     *
     * @param BareRole $role
     * @return BareRoleParent[]
     */
    public function getChildren(BareRole $role): array;
}
