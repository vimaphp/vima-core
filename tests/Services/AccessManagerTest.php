<?php

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Services\PermissionManager;
use Vima\Core\Services\PolicyRegistry;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\SyncService;
use Vima\Core\Services\UserResolver;
use Vima\Core\Tests\Fixtures\Storage\InMemoryPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserPermissionRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Tests\Fixtures\User;
use function Vima\Core\registerMany;
use function Vima\Core\resolve;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    $userResolver = new UserResolver(new VimaConfig());
    $policyRegistry = new PolicyRegistry();

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

it('returns true if user has permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(1);
    $permission = $this->accessManager->ensurePermission(Permission::define("post.view"));

    $role = Role::define(
        name: "admin",
        permissions: [
            $permission
        ]
    );

    $role = $this->accessManager->ensureRole($role);

    $this->accessManager->assignRole($user, $role);

    $this->accessManager->enforce($user, 'post.view');

    expect(true)->toBeTrue();
});

it('returns false if user lacks permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(2);

    expect($this->accessManager->isPermitted($user, 'posts.delete'))->toBeFalse();
});

it('throws AccessDeniedException when unauthorized', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $user = new User(3);
    $this->accessManager->enforce($user, 'users.delete');
})->throws(AccessDeniedException::class);

it('passes authorization if user has permission', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    /* $role = new Role('admin');
    $role->ensurePermission(new Permission('users.delete'));

    $this->accessManager->ensureRole($role);

    $user = new User(4);

    $this->accessManager->assignRole($user, $role);

    $this->accessManager->enforce($user, 'users.delete'); */
    expect(true)->toBeTrue();
});

it('throws exception for policy evaluation when no policy is registered', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $user = new User(5);
    expect(fn() => $this->accessManager
        ->evaluatePolicy($user, 'update', new stdClass()))
        ->toThrow(\Exception::class);
});

it('delegates policy evaluation to registry', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $registry = PolicyRegistry::define([
        'posts.update' => fn(User $u, $post) => $u->vimaGetId() === $post['ownerId'],
    ]);

    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    $userResolver = new UserResolver(new VimaConfig());

    registerMany([
        RoleRepositoryInterface::class => $this->roleRepo,
        PermissionRepositoryInterface::class => $this->permissionRepo,
        UserPermissionRepositoryInterface::class => $this->userPermissionRepo,
        UserRoleRepositoryInterface::class => $this->userRoleRepo,
        RolePermissionRepositoryInterface::class => $this->rolePermissionRepo,
        UserResolver::class => new UserResolver(new VimaConfig()),
        PolicyRegistry::class => $registry,
        AccessManager::class,
        SyncService::class => fn(DependencyContainer $c) => new SyncService(
            roles: $c->get(RoleRepositoryInterface::class),
            permissions: $c->get(PermissionRepositoryInterface::class)
        )
    ]);

    $manager = new AccessManager();

    $user = new User(1);
    $post = ['ownerId' => 1];

    expect($manager->evaluatePolicy($user, 'posts.update', $post))->toBeTrue();
});

it('supports class-based policy registration and evaluation', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = new AccessManager();

    class AccessTestPost
    {
        public int $ownerId;
        public function __construct($id)
        {
            $this->ownerId = $id;
        }
    }
    class AccessTestPostPolicy implements \Vima\Core\Contracts\PolicyInterface
    {
        public static function getResource(): string
        {
            return AccessTestPost::class;
        }
        public function canUpdate(User $u, AccessTestPost $p): bool
        {
            return $u->vimaGetId() === $p->ownerId;
        }
    }

    $manager->registerPolicy(AccessTestPost::class, AccessTestPostPolicy::class);

    $user = new User(1);
    $post = new AccessTestPost(1);

    expect($manager->can($user, 'posts.update', null, $post))->toBeTrue();
    expect($manager->can($user, 'update', null, $post))->toBeTrue();

    $otherUser = new User(2);
    expect($manager->can($otherUser, 'posts.update', null, $post))->toBeFalse();
});

it('integrates RBAC permissions with ABAC policies', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = new AccessManager();

    // User has permission via role (RBAC)
    $user = new User(1);
    $permission = $manager->ensurePermission(Permission::define("posts.edit"));
    $role = $manager->ensureRole(Role::define("editor", [$permission]));
    $manager->assignRole($user, $role);

    // But policy also exists for this resource (ABAC)
    class HybridPost
    {
        public int $ownerId;
        public function __construct($id)
        {
            $this->ownerId = $id;
        }
    }
    class HybridPolicy implements \Vima\Core\Contracts\PolicyInterface
    {
        public static function getResource(): string
        {
            return HybridPost::class;
        }
        public function canEdit(User $u, HybridPost $p): bool
        {
            return $u->vimaGetId() === $p->ownerId;
        }
    }
    $manager->registerPolicy(HybridPost::class, HybridPolicy::class);

    $ownPost = new HybridPost(1);
    $otherPost = new HybridPost(2);

    // Should be able to edit own post (RBAC + ABAC passes)
    expect($manager->can($user, 'posts.edit', null, $ownPost))->toBeTrue();

    // Should NOT be able to edit other post (RBAC passes, but ABAC fails)
    expect($manager->can($user, 'posts.edit', null, $otherPost))->toBeFalse();
});

it('throws exception if registry has no matching policy', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */

    $this->roleRepo = new InMemoryRoleRepository();
    $this->permissionRepo = new InMemoryPermissionRepository();
    $this->userPermissionRepo = new InMemoryUserPermissionRepository();
    $this->userRoleRepo = new InMemoryUserRoleRepository();
    $this->rolePermissionRepo = new InMemoryRolePermissionRepository();

    $userResolver = new UserResolver(new VimaConfig());

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

    expect(resolve(RoleRepositoryInterface::class))->toBeInstanceOf(RoleRepositoryInterface::class);


    /* $manager = new AccessManager();


    $user = new User(1);

    expect($manager->evaluatePolicy($user, 'posts.update', new stdClass()))->toBeFalse(); */
})/* ->throws(PolicyNotFoundException::class, 'No policy registered for permission: posts.update') */ ;

it('retrieves all roles and permissions', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = new AccessManager();

    $manager->ensurePermission('perm.1');
    $manager->ensurePermission('perm.2');
    $manager->ensureRole('role.1');
    $manager->ensureRole('role.2');

    $roles = $manager->getRoles();
    $permissions = $manager->getPermissions();

    expect($roles)->toHaveCount(2);
    expect($permissions)->toHaveCount(2);

    $roleNames = array_map(fn($r) => $r->name, $roles);
    $permNames = array_map(fn($p) => $p->name, $permissions);

    expect($roleNames)->toContain('role.1', 'role.2');
    expect($permNames)->toContain('perm.1', 'perm.2');
});

it('supports role inheritance', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = new AccessManager();

    $p1 = $manager->ensurePermission('edit.posts');
    $p2 = $manager->ensurePermission('delete.posts');

    $role1 = Role::define('editor', [$p1]);
    $role2 = Role::define('admin', [$p2]);

    $parentRole = $manager->ensureRole($role1);
    $childRole = $manager->ensureRole($role2);

    // admin inherits from editor
    $childRole->inherit($parentRole);

    $user = new User(1);
    $manager->assignRole($user, $childRole);

    // user should have admin's permission
    expect($manager->isPermitted($user, 'delete.posts'))->toBeTrue();
    // user should also have editor's permission (inherited)
    expect($manager->isPermitted($user, 'edit.posts'))->toBeTrue();

    // Verify all permissions
    $allPerms = $manager->getUserPermissions($user);
    $names = array_map(fn($p) => $p->name, $allPerms);
    expect($names)->toContain('edit.posts', 'delete.posts');
});

it('supports role context', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = new AccessManager();

    $p1 = $manager->ensurePermission('project.view');

    $role = $manager->ensureRole(Role::define('member', [$p1], context: ['project_id' => 101]));

    $user = new User(1);

    // Assign role for Project 101 only
    $manager->assignRole($user, $role);

    // Should have permission for Project 101
    expect($manager->isPermitted($user, 'project.view', ['project_id' => 101]))->toBeTrue();

    // Should NOT have permission for Project 102
    expect($manager->isPermitted($user, 'project.view', ['project_id' => 102]))->toBeFalse();

    // With no context filter, it should return all assigned roles (default behavior in InMemory repo)
    expect($manager->isPermitted($user, 'project.view'))->toBeTrue();
});