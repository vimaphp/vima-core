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
use Vima\Core\Entities\Bare\BareRoleParent;
use function Vima\Core\resolve;

/**
 * Class RoleParent
 * 
 * Represents role inheritance.
 */
class RoleParent
{
    public function __construct(
        public int|string|null $role_id = null,
        public int|string|null $parent_id = null,
        public int|string|null $id = null,
        public ?Role $role = null,
        public ?Role $parent = null
    ) {
    }

    public static function define(int|string $roleId, int|string $parentId): self
    {
        return new self(role_id: $roleId, parent_id: $parentId);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareRoleParent($this->id, $this->role_id, $this->parent_id);
        $saved = $manager->updateRoleParent($bare);
        $this->id = $saved->id;
        return $this;
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareRoleParent($this->id, $this->role_id, $this->parent_id);
        $manager->deleteRoleParent($bare);
    }

    public function getRole(): ?Role
    {
        if ($this->role) {
            return $this->role;
        }

        if (!$this->role_id) {
            return null;
        }

        /** @var \Vima\Core\Services\RoleManager $manager */
        $manager = resolve(\Vima\Core\Services\RoleManager::class);
        $this->role = $manager->resolveRole($this->role_id, isId: true);
        return $this->role;
    }

    public function getParent(): ?Role
    {
        if ($this->parent) {
            return $this->parent;
        }

        if (!$this->parent_id) {
            return null;
        }

        /** @var \Vima\Core\Services\RoleManager $manager */
        $manager = resolve(\Vima\Core\Services\RoleManager::class);
        $this->parent = $manager->resolveRole($this->parent_id, isId: true);
        return $this->parent;
    }
}
