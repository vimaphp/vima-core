<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\Bare\BareUserRoleDeny;
use DateTimeInterface;

/**
 * Interface UserRoleDenyRepositoryInterface
 * 
 * Handles persistence of explicit role denials for users.
 */
interface UserRoleDenyRepositoryInterface
{
    /**
     * Add a role denial for a user.
     *
     * @param string|int $user_id
     * @param string|int $role_id
     * @param string|null $reason
     * @param DateTimeInterface|null $expiresAt
     * @return void
     */
    public function add(string|int $user_id, string|int $role_id, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void;

    /**
     * Remove a role denial for a user.
     *
     * @param string|int $user_id
     * @param string|int $role_id
     * @return void
     */
    public function remove(string|int $user_id, string|int $role_id): void;

    /**
     * Check if a user has an explicit denial for a role.
     *
     * @param string|int $user_id
     * @param string|int $role_id
     * @return bool
     */
    public function isDenied(string|int $user_id, string|int $role_id): bool;

    /**
     * Get all role denials for a user.
     *
     * @param string|int $user_id
     * @return BareUserRoleDeny[]
     */
    public function getDeniedRoles(string|int $user_id): array;
}
