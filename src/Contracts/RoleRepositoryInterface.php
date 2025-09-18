<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Role;

interface RoleRepositoryInterface
{
    public function findByName(string $name): ?Role;

    /** @return Role[] */
    public function all(): array;

    public function save(Role $role): void;

    public function delete(Role $role): void;
}
