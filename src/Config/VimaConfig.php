<?php

declare(strict_types=1);

namespace Vima\Core\Config;

use Closure;

class VimaConfig
{
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
