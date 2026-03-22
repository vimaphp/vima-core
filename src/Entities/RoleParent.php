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

namespace Vima\Core\Entities;

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use function Vima\Core\resolve;

/**
 * Class RoleParent
 * 
 * Represents a relationship where one role inherits from another.
 *
 * @package Vima\Core\Entities
 */
class RoleParent
{
    /**
     * RoleParent constructor.
     *
     * @param int|string $role_id The child role ID.
     * @param int|string $parent_id The parent role ID.
     * @param int|string|null $id Unique identifier from storage.
     */
    public function __construct(
        public int|string $role_id,
        public int|string $parent_id,
        public int|string|null $id = null
    ) {
    }

    /**
     * Static helper to define a new role parent relationship.
     *
     * @param int|string $role_id
     * @param int|string $parent_id
     * @return self
     */
    public static function define(int|string $role_id, int|string $parent_id): self
    {
        return new self($role_id, $parent_id);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updateRoleParent($this);
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $manager->deleteRoleParent($this);
    }

    /**
     * Get the child role entity.
     *
     * @return Role
     */
    public function getRole(): Role
    {
        /** @var RoleRepositoryInterface $repo */
        $repo = resolve(RoleRepositoryInterface::class);
        return $repo->findById($this->role_id);
    }

    /**
     * Get the parent role entity.
     *
     * @return Role
     */
    public function getParent(): Role
    {
        /** @var RoleRepositoryInterface $repo */
        $repo = resolve(RoleRepositoryInterface::class);
        return $repo->findById($this->parent_id);
    }
}
