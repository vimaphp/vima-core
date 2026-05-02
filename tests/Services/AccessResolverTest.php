<?php

use Vima\Core\Services\AccessResolver;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Config\Setup;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BarePermission;
use Vima\Core\Services\RoleManager;
use Vima\Core\Services\PermissionManager;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

test('it resolves a role successfully', function () {
    $repo = resolve(RoleRepositoryInterface::class);
    $role = new BareRole(id: 1, name: 'admin');
    $repo->save($role);

    $config = new VimaConfig(
        setup: new Setup(roles: [Role::define('admin')])
    );
    $resolver = new AccessResolver(
        $config->setup, 
        resolve(RoleManager::class), 
        resolve(PermissionManager::class)
    );

    $result = $resolver->role('admin');
    expect($result)->toBeInstanceOf(Role::class);
    expect($result->name)->toBe('admin');
});

test('it throws exception if role not defined in setup', function () {
    $config = new VimaConfig();
    $resolver = new AccessResolver(
        $config->setup, 
        resolve(RoleManager::class), 
        resolve(PermissionManager::class)
    );
    $resolver->role('admin');
})->throws(\Vima\Core\Exceptions\RoleNotFoundException::class);

test('it throws exception if role defined in setup but not in storage', function () {
    $config = new VimaConfig(
        setup: new Setup(roles: [Role::define('admin')])
    );
    $resolver = new AccessResolver(
        $config->setup, 
        resolve(RoleManager::class), 
        resolve(PermissionManager::class)
    );
    $resolver->role('admin');
})->throws(\Vima\Core\Exceptions\RoleNotFoundException::class);

test('it resolves a permission successfully', function () {
    $repo = resolve(PermissionRepositoryInterface::class);
    $perm = new BarePermission(id: 1, name: 'edit');
    $repo->save($perm);

    $config = new VimaConfig(
        setup: new Setup(permissions: [new Permission('edit')])
    );
    $resolver = new AccessResolver(
        $config->setup, 
        resolve(RoleManager::class), 
        resolve(PermissionManager::class)
    );

    $result = $resolver->permission('edit');
    expect($result)->toBeInstanceOf(Permission::class);
    expect($result->name)->toBe('edit');
});

test('it resolves a permission defined indirectly within a role', function () {
    $repo = resolve(PermissionRepositoryInterface::class);
    $perm = new BarePermission(id: 1, name: 'edit');
    $repo->save($perm);

    $config = new VimaConfig(
        setup: new Setup(roles: [Role::define('admin', ['edit'])])
    );
    $resolver = new AccessResolver(
        $config->setup, 
        resolve(RoleManager::class), 
        resolve(PermissionManager::class)
    );

    $result = $resolver->permission('edit');
    expect($result)->toBeInstanceOf(Permission::class);
    expect($result->name)->toBe('edit');
});

test('it throws exception if permission not defined anywhere', function () {
    $config = new VimaConfig();
    $resolver = new AccessResolver(
        $config->setup, 
        resolve(RoleManager::class), 
        resolve(PermissionManager::class)
    );
    $resolver->permission('edit');
})->throws(\Vima\Core\Exceptions\PermissionNotFoundException::class);
