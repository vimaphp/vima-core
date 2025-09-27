<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Entities\UserPermission;

class InMemoryUserPermissionRepository implements UserPermissionRepositoryInterface
{
    /** @var UserPermission[] */
    private array $userPermissions = [];

    /**
     * @param int $userId
     * @return UserPermission[]
     */
    public function findByUserId(int $userId): array
    {
        return array_values(
            array_filter(
                $this->userPermissions,
                fn(UserPermission $up) => (string) $up->user_id === (string) $userId
            )
        );
    }

    public function add(UserPermission $permission): void
    {
        // Prevent duplicates (same user_id + permission_id)
        foreach ($this->userPermissions as $up) {
            if (
                (string) $up->user_id === (string) $permission->user_id &&
                (string) $up->permission_id === (string) $permission->permission_id
            ) {
                return;
            }
        }

        // Auto-generate ID if missing
        if ($permission->id === null) {
            $permission->id = count($this->userPermissions) + 1;
        }

        $this->userPermissions[] = $permission;
    }

    public function remove(UserPermission $permission): void
    {
        $this->userPermissions = array_values(
            array_filter(
                $this->userPermissions,
                fn(UserPermission $up) =>
                !(
                    (string) $up->user_id === (string) $permission->user_id &&
                    (string) $up->permission_id === (string) $permission->permission_id
                )
            )
        );
    }
}
