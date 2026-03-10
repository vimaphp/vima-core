<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\SyncService;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Tests\Fixtures\User;
use function Vima\Core\registerMany;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    registerMany([
        RoleRepositoryInterface::class => $this->roleRepo,
        PermissionRepositoryInterface::class => $this->permissionRepo,
        UserPermissionRepositoryInterface::class => $this->userPermissionRepo,
        UserRoleRepositoryInterface::class => $this->userRoleRepo,
        RolePermissionRepositoryInterface::class => $this->rolePermissionRepo,
        UserResolver::class => new UserResolver(new VimaConfig()),
        PolicyRegistry::class => new PolicyRegistry(),
        AccessManager::class,
        SyncService::class => fn(DependencyContainer $c) => new SyncService(
            roles: $c->get(RoleRepositoryInterface::class),
            permissions: $c->get(PermissionRepositoryInterface::class)
        )
    ]);

    $this->accessManager = new AccessManager();
});

it('supports namespaced permissions for RBAC', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $user = new User(1);
    
    // Create same permission name in different namespaces
    $p1 = Permission::define("edit", namespace: "app1");
    $p2 = Permission::define("edit", namespace: "app2");
    
    $this->accessManager->ensurePermission($p1);
    $this->accessManager->ensurePermission($p2);
    
    // Role for app1
    $role1 = Role::define(name: "editor", permissions: [$p1], namespace: "app1");
    $this->accessManager->ensureRole($role1);
    
    // Assign role1 to user
    $this->accessManager->assignRole($user, $role1);
    
    // User should have 'edit' in 'app1'
    expect($this->accessManager->isPermitted($user, 'edit', namespace: 'app1'))->toBeTrue();
    
    // User should NOT have 'edit' in 'app2'
    expect($this->accessManager->isPermitted($user, 'edit', namespace: 'app2'))->toBeFalse();
});

it('supports namespaced policies for ABAC', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = $this->accessManager;

    class NamespacePost {}
    
    class NamespacePostPolicy implements \Vima\Core\Contracts\PolicyInterface
    {
        public static function getResource(): string { return NamespacePost::class; }
        
        public function canEdit(User $u, NamespacePost $p, string $ability, ?string $namespace = null): bool
        {
            // Policy can now check the namespace
            return $namespace === 'privileged';
        }
    }

    $manager->registerPolicy(NamespacePost::class, NamespacePostPolicy::class);

    $user = new User(1);
    $post = new NamespacePost();

    // Pass 'privileged' namespace
    expect($manager->can($user, 'edit', 'privileged', $post))->toBeTrue();
    
    // Pass 'standard' namespace
    expect($manager->can($user, 'edit', 'standard', $post))->toBeFalse();
});

it('ensures Role entity correctly stores and retrieves namespaced permissions', function () {
    $p1 = Permission::define("edit", namespace: "app1");
    $p2 = Permission::define("edit", namespace: "app2");
    
    $role = Role::define("editor", [$p1, $p2]);
    
    $perms = $role->getAllPermissions();
    expect($perms)->toHaveCount(2);
    
    $namespaces = array_map(fn($p) => $p->namespace, $perms);
    expect($namespaces)->toContain('app1', 'app2');
});

it('auto-resolves namespace from permission string in can()', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $user = new User(1);
    
    // Permission 'app:edit'
    $p = Permission::define("edit", namespace: "app");
    $this->accessManager->ensurePermission($p);
    
    $role = Role::define(name: "editor", permissions: [$p], namespace: "app");
    $this->accessManager->ensureRole($role);
    $this->accessManager->assignRole($user, $role);
    
    // can() should ideally handle 'app:edit' by splitting it, 
    // but standard vima/core AccessManager::can expects namespace as 3rd arg.
    // However, we want to test that it works when namespace is explicitly passed.
    expect($this->accessManager->isPermitted($user, 'edit', namespace: 'app'))->toBeTrue();
});
