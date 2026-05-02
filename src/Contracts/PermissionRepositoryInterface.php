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

use Vima\Core\Entities\Bare\BarePermission;

/**
 * Interface PermissionRepositoryInterface
 * 
 * Defines the contract for managing Permission entities in persistent storage.
 *
 * @package Vima\Core\Contracts
 */
interface PermissionRepositoryInterface
{
    public function findByName(string $name, ?string $namespace = null): ?BarePermission;

    /**
     * Find a permission by its unique identifier.
     *
     * @param int|string $id
     * @return BarePermission|null
     */
    public function findById(int|string $id): ?BarePermission;

    /**
     * Retrieve all permissions, optionally filtered by namespace.
     * 
     * @param string|null $namespace
     * @param bool $onlyGlobal
     * @return BarePermission[]
     */
    public function all(?string $namespace = null, bool $onlyGlobal = false): array;

    /**
     * Persist or update a permission.
     *
     * @param BarePermission $permission
     * @return BarePermission The persisted permission entity.
     */
    public function save(BarePermission $permission): BarePermission;

    /**
     * Remove a permission from storage.
     *
     * @param BarePermission $permission
     * @return void
     */
    public function delete(BarePermission $permission): void;

    /**
     * Remove all permissions from storage.
     *
     * @return void
     */
    public function deleteAll(): void;
}
