<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\UserRole;
use \Vima\Core\Entities\Role;

interface UserRoleRepositoryInterface
{
    /**
     * Returns role associated with the user
     * 
     * @param int|string $user_id
     * @param bool $resolve Whether to resolve the permssions for the roles too
     * 
     * @return Role[] role names 
     */
    public function getRolesForUser(int|string $user_id, bool $resolve = false): array;

    /**
     * Assigns a role to a user
     * @param \Vima\Core\Entities\UserRole $userRole
     * @return void
     */
    public function assign(UserRole $userRole): void;

    /**
     * Removes a role assigned to a user
     * @param \Vima\Core\Entities\UserRole $userRole
     * @return void
     */
    public function revoke(UserRole $userRole): void;
}
