<?php

use Vima\Core\Support\FrameworkIntegration;
use Vima\Core\Config\Tables;
use Vima\Core\Config\Columns;
use Vima\Core\Schema\Schema;
use Vima\Core\Services\PolicyRegistry;

it('returns required tables', function () {
    $tables = FrameworkIntegration::requiredTables();
    expect($tables)->toBeInstanceOf(Tables::class);
    expect($tables->roles)->toBe('roles');
});

it('returns required columns', function () {
    $columns = FrameworkIntegration::requiredColumns();
    expect($columns)->toBeInstanceOf(Columns::class);
    expect($columns->roles->name)->toBe('name');
});

it('generates the full schema', function () {
    $schema = FrameworkIntegration::getSchema();
    expect($schema)->toBeInstanceOf(Schema::class);
    
    $tables = $schema->getTables();
    expect($tables)->toHaveCount(9);
    expect(array_keys($tables))->toContain('roles', 'permissions', 'user_roles');
});

it('returns repository contracts', function () {
    $contracts = FrameworkIntegration::repositoryContracts();
    expect($contracts->roles)->toBe(\Vima\Core\Contracts\RoleRepositoryInterface::class);
    expect($contracts->permissions)->toBe(\Vima\Core\Contracts\PermissionRepositoryInterface::class);
});

it('returns the policy registry instance', function () {
    $registry = FrameworkIntegration::policyRegistry();
    expect($registry)->toBeInstanceOf(PolicyRegistry::class);
});

it('defines helper descriptions', function () {
    $helpers = FrameworkIntegration::helpers();
    expect($helpers->vima)->toBe('Returns AccessManager service instance');
});
