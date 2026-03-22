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

use Vima\Core\Entities\Permission;
use Vima\Core\Entities\UserDeny;

/**
 * Interface UserDenyRepositoryInterface
 * 
 * Handles persistence of explicit permission denials for users.
 */
interface UserDenyRepositoryInterface
{
    /**
     * Add a denial for a user.
     *
     * @param string|int $user_id
     * @param string|int $permission_id
     * @return void
     */
    public function add(string|int $user_id, string|int $permission_id, ?string $reason = null): void;

    /**
     * Remove a denial for a user.
     *
     * @param string|int $user_id
     * @param string|int $permission_id
     * @return void
     */
    public function remove(string|int $user_id, string|int $permission_id): void;

    /**
     * Check if a user has an explicit denial for a permission.
     *
     * @param string|int $user_id
     * @param string|int $permission_id
     * @return bool
     */
    public function isDenied(string|int $user_id, string|int $permission_id): bool;

    /**
     * Get all direct permission denials for a user.
     *
     * @param string|int $user_id
     * @return UserDeny[]
     */
    public function getDeniedPermissions(string|int $user_id): array;
}
