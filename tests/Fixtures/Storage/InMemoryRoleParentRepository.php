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

namespace Vima\Core\Tests\Fixtures\Storage;

use Vima\Core\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Contracts\RoleRepositoryInterface;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\RoleParent;
use function Vima\Core\resolve;

class InMemoryRoleParentRepository implements RoleParentRepositoryInterface
{
    private array $relationships = [];

    public function assign(RoleParent $relationship): void
    {
        $key = (string) $relationship->role_id . ':' . (string) $relationship->parent_id;
        $this->relationships[$key] = $relationship;
    }

    public function remove(RoleParent $relationship): void
    {
        $key = (string) $relationship->role_id . ':' . (string) $relationship->parent_id;
        unset($this->relationships[$key]);
    }

    public function clearParents(Role $role): void
    {
        $roleId = (string) $role->id;
        $this->relationships = array_filter($this->relationships, fn($rel) => (string)$rel->role_id !== $roleId);
    }

    public function getParents(Role $role): array
    {
        $roleId = (string) $role->id;
        $parentIds = [];
        foreach ($this->relationships as $rel) {
            if ((string)$rel->role_id === $roleId) {
                $parentIds[] = $rel->parent_id;
            }
        }

        /** @var RoleRepositoryInterface $roleRepo */
        $roleRepo = resolve(RoleRepositoryInterface::class);
        $parents = [];
        foreach ($parentIds as $id) {
            $parent = $roleRepo->findById($id, true);
            if ($parent) {
                $parents[] = $parent;
            }
        }

        return $parents;
    }

    public function getChildren(Role $role): array
    {
        $parentId = (string) $role->id;
        $childIds = [];
        foreach ($this->relationships as $rel) {
            if ((string)$rel->parent_id === $parentId) {
                $childIds[] = $rel->role_id;
            }
        }

        /** @var RoleRepositoryInterface $roleRepo */
        $roleRepo = resolve(RoleRepositoryInterface::class);
        $children = [];
        foreach ($childIds as $id) {
            $child = $roleRepo->findById($id, true);
            if ($child) {
                $children[] = $child;
            }
        }

        return $children;
    }
}
