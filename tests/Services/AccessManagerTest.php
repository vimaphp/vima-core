<?php

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\DTOs\AccessContext;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Tests\Fixtures\User;

use function Vima\Core\resolve;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    initDependencies();

    $policyRegistry = resolve(PolicyRegistryInterface::class);

    //$policyRegistry->register('posts.update', function (AccessContext $ctx, $post) {
    //    dd($ctx);
    //    return $ctx->resolveId() === $post['ownerId'];
    //});

    $this->accessManager = resolve(AccessManager::class);
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

    $role = new Role('admin');
    $role->permit(new Permission('users.delete'));

    $this->accessManager->ensureRole($role);

    $user = new User(4);

    $this->accessManager->assignRole($user, $role);

    $this->accessManager->enforce($user, 'users.delete');
    expect(true)->toBeTrue();
});

it('throws exception for policy evaluation when no policy is registered', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $user = new User(5);
    expect(fn() => $this->accessManager
        ->evaluatePolicy($user, 'update', null, new stdClass()))
        ->toThrow(\Exception::class);
});

it('delegates policy evaluation to registry', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    initDependencies();

    $registry = resolve(PolicyRegistryInterface::class);
    $registry->register('posts.update', fn(AccessContext $ctx, $post) => $ctx->resolveId() === $post['ownerId']);

    $manager = resolve(AccessManager::class);

    $user = new User(1);
    $post = ['ownerId' => 1];

    expect($manager->evaluatePolicy($user, 'posts.update', null, $post))->toBeTrue();
});

it('supports class-based policy registration and evaluation', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = resolve(AccessManager::class);

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
        public function canUpdate(AccessContext $ctx, AccessTestPost $p): bool
        {
            return $ctx->owns($p, 'ownerId');
        }
    }

    $manager->registerPolicy(AccessTestPost::class, AccessTestPostPolicy::class);

    $user = new User(1);
    $post = new AccessTestPost(1);

    expect($manager->can($user, 'users:posts.update', null, $post))->toBeTrue();
    expect($manager->can($user, 'update', null, $post))->toBeTrue();

    $otherUser = new User(2);
    expect($manager->can($otherUser, 'posts.update', null, $post))->toBeFalse();
});

it('integrates RBAC permissions with ABAC policies', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = resolve(AccessManager::class);

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
        public function canEdit(AccessContext $ctx, HybridPost $p): bool
        {
            return $ctx->owns($p, 'ownerId');
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

    initDependencies();

    expect(resolve(RoleRepositoryInterface::class))->toBeInstanceOf(RoleRepositoryInterface::class);


    $manager = resolve(AccessManager::class);

    $user = new User(1);

    expect($manager->evaluatePolicy($user, 'posts.update', null, new stdClass()))->toBeFalse();
})->throws(PolicyNotFoundException::class, 'No policy registered for ability/resource: posts.update');

it('retrieves all roles and permissions', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = resolve(AccessManager::class);

    $rpRepo = resolve(RoleRepositoryInterface::class);

    $rpRepo->deleteAll();

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
    $manager = resolve(AccessManager::class);

    $p1 = $manager->ensurePermission('edit.posts');
    $p2 = $manager->ensurePermission('delete.posts');

    $role1 = Role::define('editor', [$p1]);
    $role2 = Role::define('admin', [$p2]);

    $role2->inherit($role1);

    $parentRole = $manager->ensureRole($role1);
    $childRole = $manager->ensureRole($role2);

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

    /**
     * @var AccessManagerInterface $manager
     */
    $manager = resolve(AccessManager::class);

    $p1 = $manager->ensurePermission('project.view');

    $role = $manager->ensureRole(Role::define('member', [$p1], context: ['project_id' => 101]));

    $user = new User(1);

    // Assign role for Project 101 only
    $manager->assignRole($user, $role);

    //dd(resolve(RoleRepositoryInterface::class)->all(), $p1, $role, $user);

    // Should have permission for Project 101
    expect($manager->isPermitted($user, 'project.view', ['project_id' => 101]))->toBeTrue();

    // Should NOT have permission for Project 102
    expect($manager->isPermitted($user, 'project.view', ['project_id' => 102]))->toBeFalse();

    // With no context filter, it should return all assigned roles (default behavior in InMemory repo)
    expect($manager->isPermitted($user, 'project.view'))->toBeTrue();
});

it('respects explicit user denial even if role permits', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);

    // Grant via role
    $permission = $manager->ensurePermission('posts.delete');
    $role = $manager->ensureRole(Role::define('admin', [$permission]));
    $manager->assignRole($user, $role);

    // Verify permitted
    expect($manager->can($user, 'posts.delete'))->toBeTrue();

    // Explicitly deny
    $manager->deny($user, 'posts.delete', 'Not allowed right now');

    // Verify denied
    expect($manager->can($user, 'posts.delete'))->toBeFalse();
    expect($manager->isDenied($user, 'posts.delete'))->toBeTrue();

    // Undeny and verify permitted again
    $manager->undeny($user, 'posts.delete');
    expect($manager->can($user, 'posts.delete'))->toBeTrue();
});

it('respects namespaces in permissions and denials', function () {
    $manager = resolve(AccessManager::class);
    $user = new User(1);

    // Grant namespaced permission via role
    $permission = $manager->ensurePermission('posts.publish', null, 'blog');
    $role = $manager->ensureRole(Role::define('publisher', [$permission]));
    $manager->assignRole($user, $role);

    expect($manager->can($user, 'posts.publish', 'blog'))->toBeTrue();
    expect($manager->can($user, 'blog:posts.publish'))->toBeTrue();

    // Deny the namespaced permission
    $manager->deny($user, 'blog:posts.publish');

    expect($manager->can($user, 'posts.publish', 'blog'))->toBeFalse();
    expect($manager->can($user, 'blog:posts.publish'))->toBeFalse();
});

it('can detach a role from a user', function () {
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);
    $manager->addRole('temp');
    $manager->assignRole($user, 'temp');

    expect($manager->hasRole($user, 'temp'))->toBeTrue();

    $manager->detachRole($user, 'temp');
    expect($manager->hasRole($user, 'temp'))->toBeFalse();
});

it('can manage role parents directly', function () {
    $manager = resolve(AccessManager::class);
    $child = $manager->addRole('child');
    $parent = $manager->addRole('parent');

    $rp = $manager->updateRoleParent(new \Vima\Core\Entities\Bare\BareRoleParent(
        role_id: $child->id,
        parent_id: $parent->id
    ));
    expect($rp)->toBeInstanceOf(\Vima\Core\Entities\Bare\BareRoleParent::class);

    $parents = $manager->getRoleParents($child);
    expect($parents)->toHaveCount(1);

    $manager->deleteRoleParent($rp);
    expect($manager->getRoleParents($child))->toHaveCount(0);
});

it('can revoke direct permissions', function () {
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);
    $manager->addPermission('direct.perm');
    $manager->permit($user, 'direct.perm');

    expect($manager->can($user, 'direct.perm'))->toBeTrue();

    $manager->forbid($user, 'direct.perm');
    expect($manager->can($user, 'direct.perm'))->toBeFalse();
});

it('retrieves user permissions with context', function () {
    /**
     * @var AccessManagerInterface $manager
     */
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);

    $p1 = $manager->ensurePermission('p1');
    $p2 = $manager->ensurePermission('p2');

    $role1 = $manager->addRole('r1');
    $role1->permit($p1);
    $role1->save();

    $role2 = $manager->addRole('r2');
    $role2->permit($p2);
    $role2->save();

    $manager->assignRole($user, $role1); // global
    $manager->assignRole($user, $role2); // contextual

    $perms = $manager->getUserPermissions($user);
    expect($perms)->toHaveCount(2);

    $contextPerms = $manager->getUserPermissions($user, ['org' => 1]);
    expect($contextPerms)->toHaveCount(1);
    expect($contextPerms[0]->name)->toBe('p2');
})->skip('Contextual permissions not fully implemented yet');

it('can register policies via govern', function () {
    /**
     * @var AccessManagerInterface $manager
     */
    $manager = resolve(AccessManager::class);
    $user = new UserMock(1);

    $manager->govern('posts.edit', fn(AccessContext $ctx) => $ctx->resolveId() === 1);

    expect($manager->can($user, 'posts.edit', null, ['post']))->toBeTrue();
    expect($manager->can(new UserMock(2), 'posts.edit', null, ['post']))->toBeFalse();
});

it('retrieves roles and permissions with various filters', function () {
    $manager = resolve(AccessManager::class);
    $manager->addRole('r1', namespace: 'ns1');
    $manager->addPermission('p1', namespace: 'ns1');

    expect($manager->getRoles('ns1'))->toHaveCount(1);
    expect($manager->getPermissions('ns1'))->toHaveCount(1);

    $user = new UserMock(1);
    $manager->deny($user, 'ns1:p1');

    $perms = $manager->getPermissions('ns1', $user);
    expect($perms[0]->denied)->toBeTrue();
});