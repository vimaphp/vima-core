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

use Vima\Core\Entities\Role;

/**
 * Interface RoleRepositoryInterface
 * 
 * Defines the contract for managing Role entities in persistent storage.
 *
 * @package Vima\Core\Contracts
 */
interface RoleRepositoryInterface
{
    /**
     * Find a role by its unique name.
     *
     * @param string $name
     * @return Role|null
     */
    public function findByName(string $name): ?Role;

    /**
     * Find a role by its unique identifier.
     *
     * @param int|string $id
     * @param bool $resolve Whether to resolve related permissions.
     * @return Role|null
     */
    public function findById(int|string $id, bool $resolve = false): ?Role;

    /**
     * Retrieve all roles.
     * 
     * @return Role[]
     */
    public function all(): array;

    /**
     * Persist or update a role.
     *
     * @param Role $role
     * @return Role The persisted role entity.
     */
    public function save(Role $role): Role;

    /**
     * Remove a role from storage.
     *
     * @param Role $role
     * @return void
     */
    public function delete(Role $role): void;

    /**
     * Remove all roles from storage.
     *
     * @return void
     */
    public function deleteAll(): void;
}
