<?php
namespace Vima\Core\Contracts;

use Vima\Core\Entities\UserPermission;

interface UserPermissionRepositoryInterface
{
    /**
     * Returns the user permissions
     * @param int $userId
     * @return UserPermission[]
     */
    public function findByUserId(int $userId): array;

    /**
     * Add the user permissions mappin to storage
     * @param \Vima\Core\Entities\UserPermission $permission
     * @return void
     */
    public function add(UserPermission $permission): void;

    /**
     * Removes user permission to storage
     * @param \Vima\Core\Entities\UserPermission $permission
     * @return void
     */
    public function remove(UserPermission $permission): void;
}
