<?php

namespace Vima\Core\Tests\Fixtures;

use Vima\Core\Contracts\UserInterface;
use Vima\Core\Entities\Role;

class User implements UserInterface
{
    public function __construct(
        private string|int $id,
        /** @var Role[] */
        private array $roles = []
    ) {
    }

    public function vimaGetId(): string|int
    {
        return $this->id;
    }

    /** @return Role[] */
    public function vimaGetRoles(): array
    {
        return $this->roles;
    }

    public function ensureRole(Role $role): void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function detachRole(Role $role): void
    {
        $this->roles = array_filter(
            $this->roles,
            fn($r) => $r !== $role
        );
    }

    public function isPermitted(string ...$permissions): bool
    {
        foreach ($permissions as $p) {
            foreach ($this->roles as $r) {
                if ($r->isPermitted($p)) {
                    return true;
                }
            }
        }

        return false;
    }
}
