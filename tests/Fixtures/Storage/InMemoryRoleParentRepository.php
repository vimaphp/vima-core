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
use Vima\Core\Entities\Bare\BareRole;
use Vima\Core\Entities\Bare\BareRoleParent;

class InMemoryRoleParentRepository implements RoleParentRepositoryInterface
{
    /** @var BareRoleParent[] */
    private array $relationships = [];

    public function assign(BareRoleParent $relationship): void
    {
        $key = (string) $relationship->role_id . ':' . (string) $relationship->parent_id;
        $this->relationships[$key] = $relationship;
    }

    public function remove(BareRoleParent $relationship): void
    {
        $key = (string) $relationship->role_id . ':' . (string) $relationship->parent_id;
        unset($this->relationships[$key]);
    }

    public function clearParents(BareRole $role): void
    {
        $roleId = (string) $role->id;
        $this->relationships = array_filter($this->relationships, fn($rel) => (string)$rel->role_id !== $roleId);
    }

    public function getParents(BareRole $role): array
    {
        $roleId = (string) $role->id;
        return array_values(
            array_filter($this->relationships, fn($rel) => (string)$rel->role_id === $roleId)
        );
    }

    public function getChildren(BareRole $role): array
    {
        $parentId = (string) $role->id;
        return array_values(
            array_filter($this->relationships, fn($rel) => (string)$rel->parent_id === $parentId)
        );
    }
}
