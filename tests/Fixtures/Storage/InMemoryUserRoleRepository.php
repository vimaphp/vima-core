<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\DependencyContainer;
use Vima\Core\Entities\UserRole;
use Vima\Core\Entities\Role;
use function Vima\Core\resolve;

class InMemoryUserRoleRepository implements UserRoleRepositoryInterface
{
    /** @var UserRole[] */
    public array $userRoles = [];

    /** @var Role[] indexed by role_id */
    private array $roles = [];

    public function __construct(array $roles = [])
    {
        // preload available roles if passed
        foreach ($roles as $role) {
            if ($role instanceof Role && $role->id !== null) {
                $this->roles[(string) $role->id] = $role;
            }
        }
    }

    /**
     * @param int|string $user_id
     * @param bool $resolve Whether to return full Role objects with permissions or not
     * @return Role[]
     */
    public function getRolesForUser(int|string $user_id, bool $resolve = false): array
    {
        $roles = [];

        /** @var RoleRepositoryInterface */
        $rolesRepo = resolve(RoleRepositoryInterface::class);

        foreach ($this->userRoles as $ur) {
            if ((string) $user_id === (string) $ur->user_id) {
                $role = $rolesRepo->findById($ur->role_id, $resolve);

                $roles[] = $role;
            }
        }

        return $roles;
    }

    public function assign(UserRole $userRole): void
    {

        // prevent duplicate (user_id + role_id)
        foreach ($this->userRoles as $ur) {
            if (
                (string) $ur->user_id === (string) $userRole->user_id &&
                (string) $ur->role_id === (string) $userRole->role_id
            ) {
                return;
            }
        }

        // auto-generate ID
        if ($userRole->id === null) {
            $userRole->id = count($this->userRoles) + 1;
        }

        $this->userRoles[] = $userRole;
    }

    public function revoke(UserRole $userRole): void
    {
        $this->userRoles = array_values(
            array_filter(
                $this->userRoles,
                fn(UserRole $ur) =>
                !(
                    (string) $ur->user_id === (string) $userRole->user_id &&
                    (string) $ur->role_id === (string) $userRole->role_id
                )
            )
        );
    }
}
