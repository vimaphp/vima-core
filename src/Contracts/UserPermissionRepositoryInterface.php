<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Bare\BareUserPermission;

interface UserPermissionRepositoryInterface
{
    /**
     * Returns the user permissions
     * @param int|string $userId
     * @return BareUserPermission[]
     */
    public function findByUserId(int|string $userId): array;

    /**
     * Add the user permissions mappin to storage
     * @param BareUserPermission $permission
     * @return void
     */
    public function add(BareUserPermission $permission): void;

    /**
     * Removes user permission to storage
     * @param BareUserPermission $permission
     * @return void
     */
    public function remove(BareUserPermission $permission): void;
}
