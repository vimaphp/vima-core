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

namespace Vima\Core\Services;

use Vima\Core\Config\Setup;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Exception;

use Vima\Core\Services\RoleManager;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Exceptions\RoleNotFoundException;
use Vima\Core\Exceptions\PermissionNotFoundException;

/**
 * Class AccessResolver
 * 
 * Helper to resolve roles and permissions from Setup config and verify their existence.
 */
class AccessResolver
{
    public function __construct(
        private Setup $setup,
        private RoleManager $roleManager,
        private PermissionManager $permissionManager
    ) {
    }

    /**
     * Resolve a role by name or object.
     *
     * @param string|Role $role Role name or object.
     * @return Role
     * @throws RoleNotFoundException If role is not defined in Setup or not found in storage.
     */
    public function role(string|Role $role): Role
    {
        $name = $role instanceof Role ? $role->name : $role;

        // 1. Check Setup config (Source of Truth)
        $defined = false;
        foreach ($this->setup->roles as $r) {
            if ($r->name === $name) {
                $defined = true;
                break;
            }
        }

        if (!$defined) {
            throw new RoleNotFoundException("Role '{$name}' is not defined in the application Setup.");
        }

        // 2. Fetch from storage to ensure it's synced
        $stored = $this->roleManager->find($name);

        if (!$stored) {
            throw new RoleNotFoundException("Role '{$name}' exists in Setup but was not found in storage. Did you sync?");
        }

        return $stored;
    }

    /**
     * Resolve a permission by name or object.
     *
     * @param string|Permission $permission Permission name or object.
     * @return Permission
     * @throws PermissionNotFoundException If permission is not defined in Setup or not found in storage.
     */
    public function permission(string|Permission $permission): Permission
    {
        $name = $permission instanceof Permission ? $permission->name : $permission;

        // 1. Check Setup config (Source of Truth)
        $defined = false;
        foreach ($this->setup->permissions as $p) {
            if ($p->name === $name) {
                $defined = true;
                break;
            }
        }

        // Also check if it's indirectly defined within roles
        if (!$defined) {
            foreach ($this->setup->roles as $r) {
                foreach ($r->permissions as $p) {
                    if ($p->name === $name) {
                        $defined = true;
                        break 2;
                    }
                }
            }
        }

        if (!$defined) {
            throw new PermissionNotFoundException("Permission '{$name}' is not defined in the application Setup.");
        }

        // 2. Fetch from storage
        $stored = $this->permissionManager->find($name);

        if (!$stored) {
            throw new PermissionNotFoundException("Permission '{$name}' exists in Setup but was not found in storage. Did you sync?");
        }

        return $stored;
    }
}
