<?php

use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\RolePermission;
use Vima\Core\Entities\RoleParent;
use Vima\Core\Entities\UserRole;
use Vima\Core\Entities\UserPermission;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BarePermission;
use function Vima\Core\resolve;

beforeEach(function () {
    initDependencies();
});

it('manages RolePermission lifecycle', function () {
    $roleRepo = resolve(RoleRepositoryInterface::class);
    $permRepo = resolve(PermissionRepositoryInterface::class);
    $rpRepo = resolve(RolePermissionRepositoryInterface::class);

    $role = new BareRole(id: 1, name: 'admin');
    $roleRepo->save($role);
    $perm = new BarePermission(id: 10, name: 'edit');
    $permRepo->save($perm);

    $rp = RolePermission::define(1, 10);
    $saved = $rp->save();
    
    expect($saved)->toBeInstanceOf(RolePermission::class);
    expect($rpRepo->getRolePermissions($role))->toHaveCount(1);
    
    expect($rp->getRole()->name)->toBe('admin');
    expect($rp->getPermission()->name)->toBe('edit');

    $rp->delete();
    expect($rpRepo->getRolePermissions($role))->toHaveCount(0);
});

it('manages RoleParent lifecycle', function () {
    $roleRepo = resolve(RoleRepositoryInterface::class);
    $parentRepo = resolve(RoleParentRepositoryInterface::class);

    $role = new BareRole(id: 1, name: 'editor');
    $parent = new BareRole(id: 2, name: 'admin');
    $roleRepo->save($role);
    $roleRepo->save($parent);

    $rp = RoleParent::define(1, 2);
    $saved = $rp->save();

    expect($saved)->toBeInstanceOf(RoleParent::class);
    expect($parentRepo->getParents($role))->toHaveCount(1);

    expect($rp->getRole()->name)->toBe('editor');
    expect($rp->getParent()->name)->toBe('admin');

    $rp->delete();
    expect($parentRepo->getParents($role))->toHaveCount(0);
});

it('manages UserRole lifecycle', function () {
    $roleRepo = resolve(RoleRepositoryInterface::class);
    $userRepo = resolve(UserRepositoryInterface::class);
    $urRepo = resolve(UserRoleRepositoryInterface::class);

    $role = new BareRole(id: 1, name: 'admin');
    $roleRepo->save($role);
    
    $user = new UserMock(123);
    $userRepo->save($user);

    $ur = UserRole::define(123, 1);
    $saved = $ur->save();

    expect($saved)->toBeInstanceOf(UserRole::class);
    expect($urRepo->getRolesForUser(123))->toHaveCount(1);

    expect($ur->getRole()->name)->toBe('admin');
    expect($ur->getUser()->vimaGetId())->toBe(123);

    $ur->delete();
    expect($urRepo->getRolesForUser(123))->toHaveCount(0);
});

it('manages UserPermission lifecycle', function () {
    $permRepo = resolve(PermissionRepositoryInterface::class);
    $userRepo = resolve(UserRepositoryInterface::class);
    $upRepo = resolve(UserPermissionRepositoryInterface::class);

    $perm = new BarePermission(id: 5, name: 'debug');
    $permRepo->save($perm);

    $user = new UserMock(123);
    $userRepo->save($user);

    $up = UserPermission::define(123, 5);
    $saved = $up->save();

    expect($saved)->toBeInstanceOf(UserPermission::class);
    expect($upRepo->findByUserId(123))->toHaveCount(1);

    expect($up->getPermission()->name)->toBe('debug');
    expect($up->getUser()->vimaGetId())->toBe(123);

    $up->delete();
    expect($upRepo->findByUserId(123))->toHaveCount(0);
});
