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

namespace Vima\Core\Config;

use Closure;
use Vima\Core\Entities\SuperAdmin;

/**
 * Class VimaConfig
 * 
 * Main configuration object for Vima Core.
 *
 * @package Vima\Core\Config
 */
class VimaConfig
{
    /**
     * @param Tables $tables Table names for persistent storage.
     * @param Columns $columns Column names for persistent storage.
     * @param Setup $setup Declarative roles and permissions setup.
     * @param UserMethods $userMethods Mapping for user object methods.
     * @param Closure|null $registerPolicies Callback to register ABAC policies.
     * @param Closure|null $userResolver Custom user resolver logic.
     * @param bool $cacheEnabled Enable or disable caching of permissions.
     * @param int $cacheTTL Time-to-live for cache entries in seconds.
     * @param string $cachePrefix Prefix for cache keys.
     * @param SuperAdmin|string|null $superAdminRole Role name or object representing the super admin role.
     * @param bool $superAdminBypass Whether super admins bypass all permission checks
     */
    public function __construct(
        public Tables $tables = new Tables(),
        public Columns $columns = new Columns(
            roles: new RoleColumns(),
            permissions: new PermissionColumns(),
            userRoles: new UserRoleColumns(),
            rolePermissions: new RolePermissionColumns(),
            userPermissions: new UserPermissionColumns(),
            roleParents: new RoleParentColumns(),
            userDenies: new UserDenyColumns()
        ),
        public Setup $setup = new Setup(),
        public UserMethods $userMethods = new UserMethods(),

        public ?Closure $registerPolicies = null,
        public ?Closure $userResolver = null,

        public bool $cacheEnabled = false,
        public int $cacheTTL = 3600,
        public string $cachePrefix = 'vima_',

        public SuperAdmin|string|null $superAdminRole = null,
        public bool $superAdminBypass = true,
    ) {
        if ($this->registerPolicies) {
            ($this->registerPolicies)();
        }
    }
}
