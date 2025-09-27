<?php
declare(strict_types=1);

namespace Vima\Core;

use Vima\Core\Contracts\{
    RoleRepositoryInterface,
    PermissionRepositoryInterface,
    RolePermissionRepositoryInterface,
    UserRoleRepositoryInterface,
    UserPermissionRepositoryInterface,
    PolicyRegistryInterface
};
use Vima\Core\Services\{UserResolver};

class DependencyContainer
{
    public static DependencyContainer $instance;

    public function __construct(
        public RoleRepositoryInterface $roles,
        public PermissionRepositoryInterface $permissions,
        public RolePermissionRepositoryInterface $rolePermissions,
        public UserRoleRepositoryInterface $userRoles,
        public UserPermissionRepositoryInterface $userPermissions,
        public PolicyRegistryInterface $policies,
        public UserResolver $userResolver,
    ) {
        self::$instance = $this;
    }
}
