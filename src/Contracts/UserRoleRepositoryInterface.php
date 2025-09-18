<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Contracts\UserInterface;

interface UserRoleRepositoryInterface
{
    /** @return string[] role names */
    public function getRolesForUser(UserInterface $user): array;

    public function assign(UserInterface $user, string $roleName): void;

    public function revoke(UserInterface $user, string $roleName): void;
}
