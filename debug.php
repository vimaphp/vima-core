<?php
require_once __DIR__ . '/vendor/autoload.php';
use Vima\Core\Services\RoleManager;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryUserRoleRepository;
use Vima\Core\Tests\Fixtures\Storage\InMemoryRolePermissionRepository;

echo "Starting debug script...\n";
try {
    $rm = new RoleManager(
        new InMemoryRoleRepository(),
        new InMemoryUserRoleRepository(),
        new InMemoryRolePermissionRepository()
    );
    echo "RoleManager instantiated successfully.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "Done.\n";
