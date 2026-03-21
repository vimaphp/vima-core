<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\UserDenyRepositoryInterface;
use Vima\Core\Entities\Permission;

/**
 * Simple in‑memory repository for user denial records used in tests.
 *
 * It stores a map of user IDs to a set of denied permission IDs.
 * The `getDeniedPermissions` method returns an empty array because the
 * Permission entities are not needed for the current test suite – the
 * presence of a denial is sufficient.
 */
class InMemoryUserDenyRepository implements UserDenyRepositoryInterface
{
    /** @var array<string|int, array<string|int, bool>> */
    private array $denials = [];

    public function add(string|int $user_id, string|int $permission_id): void
    {
        $uid = (string) $user_id;
        $pid = (string) $permission_id;
        $this->denials[$uid][$pid] = true;
    }

    public function remove(string|int $user_id, string|int $permission_id): void
    {
        $uid = (string) $user_id;
        $pid = (string) $permission_id;
        if (isset($this->denials[$uid][$pid])) {
            unset($this->denials[$uid][$pid]);
            if (empty($this->denials[$uid])) {
                unset($this->denials[$uid]);
            }
        }
    }

    public function isDenied(string|int $user_id, string|int $permission_id): bool
    {
        $uid = (string) $user_id;
        $pid = (string) $permission_id;
        return isset($this->denials[$uid][$pid]);
    }

    public function getDeniedPermissions(string|int $user_id): array
    {
        // The test suite does not rely on the actual Permission objects.
        // Returning an empty array satisfies the contract without extra
        // dependencies on the Permission repository.
        return [];
    }
}
