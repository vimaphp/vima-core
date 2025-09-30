<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Role;

interface RoleRepositoryInterface
{
    public function findByName(string $name): ?Role;
    public function findById(int|string $id, bool $resolve = false): ?Role;

    /** @return Role[] */
    public function all(): array;

    public function save(Role $role): Role;

    public function delete(Role $role): void;
}
