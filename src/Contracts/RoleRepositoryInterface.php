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

use Vima\Core\Entities\Bare\BareRole;

/**
 * Interface RoleRepositoryInterface
 * 
 * Defines the contract for managing Role entities in persistent storage.
 *
 * @package Vima\Core\Contracts
 */
interface RoleRepositoryInterface
{
    public function findByName(string $name, ?string $namespace = null): ?BareRole;

    /**
     * Find a role by its unique identifier.
     *
     * @param int|string $id
     * @return BareRole|null
     */
    public function findById(int|string $id): ?BareRole;

    /**
     * Retrieve all roles, optionally filtered by namespace.
     * 
     * @param string|null $namespace
     * @return BareRole[]
     */
    public function all(?string $namespace = null): array;

    /**
     * Persist or update a role.
     *
     * @param BareRole $role
     * @return BareRole The persisted role entity.
     */
    public function save(BareRole $role): BareRole;

    /**
     * Remove a role from storage.
     *
     * @param BareRole $role
     * @return void
     */
    public function delete(BareRole $role): void;

    /**
     * Remove all roles from storage.
     *
     * @return void
     */
    public function deleteAll(): void;
}
