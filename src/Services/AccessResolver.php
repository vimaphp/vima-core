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

/**
 * Class AccessResolver
 * 
 * Helper to resolve roles and permissions from Setup config and verify their existence.
 */
class AccessResolver
{
    public function __construct(
        private Setup $setup,
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions
    ) {
    }

    /**
     * Resolve a role by name or object.
     *
     * @param string|Role $role Role name or object.
     * @return Role
     * @throws Exception If role is not defined in Setup or not found in storage.
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
            throw new Exception("Role '{$name}' is not defined in the application Setup.");
        }

        // 2. Fetch from storage to ensure it's synced
        $stored = $this->roles->findByName($name);

        if (!$stored) {
            throw new Exception("Role '{$name}' exists in Setup but was not found in storage. Did you sync?");
        }

        return $stored;
    }

    /**
     * Resolve a permission by name or object.
     *
     * @param string|Permission $permission Permission name or object.
     * @return Permission
     * @throws Exception If permission is not defined in Setup or not found in storage.
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
            throw new Exception("Permission '{$name}' is not defined in the application Setup.");
        }

        // 2. Fetch from storage
        $stored = $this->permissions->findByName($name);

        if (!$stored) {
            throw new Exception("Permission '{$name}' exists in Setup but was not found in storage. Did you sync?");
        }

        return $stored;
    }
}
