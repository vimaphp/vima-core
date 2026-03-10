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

/**
 * Interface PermissionRepositoryInterface
 * 
 * Defines the contract for managing Permission entities in persistent storage.
 *
 * @package Vima\Core\Contracts
 */
interface PermissionRepositoryInterface
{
    /**
     * Find a permission by its unique name, optionally within a namespace.
     *
     * @param string $name
     * @param string|null $namespace
     * @return Permission|null
     */
    public function findByName(string $name, ?string $namespace = null): ?Permission;

    /**
     * Find a permission by its unique identifier.
     *
     * @param int|string $id
     * @return Permission|null
     */
    public function findById(int|string $id): ?Permission;

    /**
     * Retrieve all permissions, optionally filtered by namespace.
     * 
     * @param string|null $namespace
     * @return Permission[]
     */
    public function all(?string $namespace = null): array;

    /**
     * Persist or update a permission.
     *
     * @param Permission $permission
     * @return Permission The persisted permission entity.
     */
    public function save(Permission $permission): Permission;

    /**
     * Remove a permission from storage.
     *
     * @param Permission $permission
     * @return void
     */
    public function delete(Permission $permission): void;

    /**
     * Remove all permissions from storage.
     *
     * @return void
     */
    public function deleteAll(): void;
}
