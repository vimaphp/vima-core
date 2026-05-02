<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\UserRoleRepositoryInterface;
use Vima\Core\Entities\Bare\BareUserRole;

class InMemoryUserRoleRepository implements UserRoleRepositoryInterface
{
    /** @var BareUserRole[] */
    public array $userRoles = [];

    /**
     * @param int|string $user_id
     * @return BareUserRole[]
     */
    public function getRolesForUser(int|string $user_id): array
    {
        return array_values(
            array_filter(
                $this->userRoles,
                fn(BareUserRole $ur) => (string) $user_id === (string) $ur->user_id
            )
        );
    }

    public function assign(BareUserRole $userRole): void
    {
        // prevent duplicate (user_id + role_id)
        foreach ($this->userRoles as $ur) {
            if (
                (string) $ur->user_id === (string) $userRole->user_id &&
                (string) $ur->role_id === (string) $userRole->role_id
            ) {
                $ur->context = $userRole->context;
                return;
            }
        }

        // auto-generate ID
        if ($userRole->id === null) {
            $userRole->id = count($this->userRoles) + 1;
        }

        $this->userRoles[] = $userRole;
    }

    public function revoke(BareUserRole $userRole): void
    {
        $this->userRoles = array_values(
            array_filter(
                $this->userRoles,
                fn(BareUserRole $ur) =>
                !(
                    (string) $ur->user_id === (string) $userRole->user_id &&
                    (string) $ur->role_id === (string) $userRole->role_id
                )
            )
        );
    }
}
