<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\UserDenyRepositoryInterface;
use Vima\Core\Entities\Bare\BareUserDeny;
use DateTimeInterface;

/**
 * Simple in‑memory repository for user denial records used in tests.
 */
class InMemoryUserDenyRepository implements UserDenyRepositoryInterface
{
    /** @var BareUserDeny[] */
    private array $denials = [];

    public function add(string|int $user_id, string|int $permission_id, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        foreach ($this->denials as $deny) {
            if ((string)$deny->user_id === (string)$user_id && (string)$deny->permission_id === (string)$permission_id) {
                $deny->reason = $reason;
                $deny->expires_at = $expiresAt?->format('Y-m-d H:i:s');
                return;
            }
        }

        $this->denials[] = new BareUserDeny(
            id: count($this->denials) + 1,
            user_id: $user_id,
            permission_id: $permission_id,
            reason: $reason,
            expires_at: $expiresAt?->format('Y-m-d H:i:s'),
            created_at: date('Y-m-d H:i:s')
        );
    }

    public function remove(string|int $user_id, string|int $permission_id): void
    {
        $this->denials = array_values(
            array_filter($this->denials, fn($d) => !((string)$d->user_id === (string)$user_id && (string)$d->permission_id === (string)$permission_id))
        );
    }

    public function isDenied(string|int $user_id, string|int $permission_id): bool
    {
        foreach ($this->denials as $deny) {
            if ((string)$deny->user_id === (string)$user_id && (string)$deny->permission_id === (string)$permission_id) {
                if ($deny->expires_at && strtotime($deny->expires_at) < time()) {
                    return false;
                }
                return true;
            }
        }

        return false;
    }

    public function getDeniedPermissions(string|int $user_id): array
    {
        return array_values(
            array_filter($this->denials, fn($d) => (string)$d->user_id === (string)$user_id)
        );
    }
}
