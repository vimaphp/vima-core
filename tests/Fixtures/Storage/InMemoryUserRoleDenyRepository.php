<?php
declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Entities\Bare\BareUserRoleDeny;
use DateTimeInterface;

class InMemoryUserRoleDenyRepository implements UserRoleDenyRepositoryInterface
{
    /** @var BareUserRoleDeny[] */
    private array $denials = [];

    public function add(string|int $user_id, string|int $role_id, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        foreach ($this->denials as $deny) {
            if ((string)$deny->user_id === (string)$user_id && (string)$deny->role_id === (string)$role_id) {
                $deny->reason = $reason;
                $deny->expires_at = $expiresAt?->format('Y-m-d H:i:s');
                return;
            }
        }

        $this->denials[] = new BareUserRoleDeny(
            id: count($this->denials) + 1,
            user_id: $user_id,
            role_id: $role_id,
            reason: $reason,
            expires_at: $expiresAt?->format('Y-m-d H:i:s'),
            created_at: date('Y-m-d H:i:s')
        );
    }

    public function remove(string|int $user_id, string|int $role_id): void
    {
        $this->denials = array_values(
            array_filter($this->denials, fn($d) => !((string)$d->user_id === (string)$user_id && (string)$d->role_id === (string)$role_id))
        );
    }

    public function isDenied(string|int $user_id, string|int $role_id): bool
    {
        foreach ($this->denials as $deny) {
            if ((string)$deny->user_id === (string)$user_id && (string)$deny->role_id === (string)$role_id) {
                if ($deny->expires_at && strtotime($deny->expires_at) < time()) {
                    return false;
                }
                return true;
            }
        }

        return false;
    }

    public function getDeniedRoles(string|int $user_id): array
    {
        return array_values(
            array_filter($this->denials, fn($d) => (string)$d->user_id === (string)$user_id)
        );
    }
}
