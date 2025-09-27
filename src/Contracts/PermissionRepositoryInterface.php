<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Permission;

interface PermissionRepositoryInterface
{
    public function findByName(string $name): ?Permission;
    public function findById(int|string $id): ?Permission;

    /** @return Permission[] */
    public function all(): array;

    public function save(Permission $permission): Permission;

    public function delete(Permission $permission): void;
}
