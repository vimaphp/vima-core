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
     */
    public function __construct(
        public Tables $tables = new Tables(),
        public Columns $columns = new Columns(
            roles: new RoleColumns(),
            permissions: new PermissionColumns(),
            userRoles: new UserRoleColumns(),
            rolePermissions: new RolePermissionColumns(),
            userPermissions: new UserPermissionColumns()
        ),
        public Setup $setup = new Setup(),
        public UserMethods $userMethods = new UserMethods(),

        public ?Closure $registerPolicies = null,
        public ?Closure $userResolver = null,
    ) {
        if ($this->registerPolicies) {
            ($this->registerPolicies)();
        }
    }
}
