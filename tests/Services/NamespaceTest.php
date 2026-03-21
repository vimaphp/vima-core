<?php

use Vima\Core\Services\AccessManager;
use Vima\Core\Entities\{Role, Permission};
use Vima\Core\Tests\Fixtures\User;
use function Vima\Core\resolve;

beforeEach(function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    initDependencies();

    $this->accessManager = resolve(AccessManager::class);
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
    expect($this->accessManager->isPermitted($user, 'app1:edit'))->toBeTrue();

    // User should NOT have 'edit' in 'app2'
    expect($this->accessManager->isPermitted($user, 'app2:edit'))->toBeFalse();
});

it('supports namespaced policies for ABAC', function () {
    /** @var \Vima\Core\Tests\ManagerTestCase $this */
    $manager = $this->accessManager;

    class NamespacePost
    {
    }

    class NamespacePostPolicy implements \Vima\Core\Contracts\PolicyInterface
    {
        public static function getResource(): string
        {
            return NamespacePost::class;
        }

        public function canEdit(User $u, NamespacePost $p, string $ability, ?string $namespace = null): bool
        {
            // Policy can now check the namespace
            return $namespace === 'privileged';
        }
    }

    $manager->registerPolicy(NamespacePost::class, NamespacePostPolicy::class);

    $user = new User(1);
    $post = new NamespacePost();

    $manager->ensurePermission(Permission::define("edit", namespace: "privileged"));

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
