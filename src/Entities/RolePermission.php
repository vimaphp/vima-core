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
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Bare\BareRolePermission;
use function Vima\Core\resolve;

/**
 * Class RolePermission
 * 
 * Represents the link between a role and a permission.
 */
class RolePermission
{
    public function __construct(
        public int|string|null $role_id = null,
        public int|string|null $permission_id = null,
        public int|string|null $id = null,
        public ?array $constraints = null,
        public ?Permission $permission = null,
        public ?Role $role = null
    ) {
    }

    public static function define(int|string $roleId, int|string $permissionId, ?array $constraints = null): self
    {
        return new self(role_id: $roleId, permission_id: $permissionId, constraints: $constraints);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareRolePermission($this->id, $this->role_id, $this->permission_id, $this->constraints);
        $saved = $manager->updateRolePermission($bare);
        $this->id = $saved->id;
        return $this;
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareRolePermission($this->id, $this->role_id, $this->permission_id, $this->constraints);
        $manager->deleteRolePermission($bare);
    }

    public function getRole(): ?Role
    {
        if ($this->role) {
            return $this->role;
        }

        if (!$this->role_id) {
            return null;
        }

        /** @var \Vima\Core\Services\RoleManager $manager */
        $manager = resolve(\Vima\Core\Services\RoleManager::class);
        $this->role = $manager->resolveRole($this->role_id, isId: true);
        return $this->role;
    }

    public function getPermission(): ?Permission
    {
        if ($this->permission) {
            return $this->permission;
        }

        if (!$this->permission_id) {
            return null;
        }

        /** @var \Vima\Core\Services\PermissionManager $manager */
        $manager = resolve(\Vima\Core\Services\PermissionManager::class);
        $this->permission = $manager->find($this->permission_id);
        return $this->permission;
    }
}
