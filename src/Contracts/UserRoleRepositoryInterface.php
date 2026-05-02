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

use Vima\Core\Entities\Bare\BareUserRole;

interface UserRoleRepositoryInterface
{
    /**
     * Returns roles assignments associated with the user
     * 
     * @param int|string $user_id
     * @return BareUserRole[]
     */
    public function getRolesForUser(int|string $user_id): array;

    /**
     * Assigns a role to a user
     * @param BareUserRole $userRole
     * @return void
     */
    public function assign(BareUserRole $userRole): void;

    /**
     * Removes a role assigned to a user
     * @param BareUserRole $userRole
     * @return void
     */
    public function revoke(BareUserRole $userRole): void;
}
