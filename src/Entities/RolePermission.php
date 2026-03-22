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

namespace Vima\Core\Entities;

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use function Vima\Core\resolve;

class RolePermission
{
    public function __construct(
        public int|string $role_id,
        public int|string $permission_id,
        public int|string|null $id = null,
    ) {
    }

    public static function define(int|string $role_id, int|string $permission_id): RolePermission
    {
        return new self(role_id: $role_id, permission_id: $permission_id);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updateRolePermission($this);
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $manager->deleteRolePermission($this);
    }

    /**
     * Get the role entity.
     *
     * @return Role
     */
    public function getRole(): Role
    {
        /** @var RoleRepositoryInterface $repo */
        $repo = resolve(RoleRepositoryInterface::class);
        return $repo->findById($this->role_id);
    }

    /**
     * Get the permission entity.
     *
     * @return Permission
     */
    public function getPermission(): Permission
    {
        /** @var PermissionRepositoryInterface $repo */
        $repo = resolve(PermissionRepositoryInterface::class);
        return $repo->findById($this->permission_id);
    }
}