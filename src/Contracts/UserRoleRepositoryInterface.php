<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

use Vima\Core\Entities\UserRole;
use \Vima\Core\Entities\Role;

interface UserRoleRepositoryInterface
{
    /**
     * Returns role associated with the user
     * @return Role[] role names 
     */
    public function getRolesForUser(object $user): array;

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
