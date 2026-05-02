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
use Vima\Core\Contracts\UserRepositoryInterface;
use Vima\Core\Entities\Bare\BareUserPermission;
use function Vima\Core\resolve;

/**
 * Class UserPermission
 * 
 * Represents a direct permission assignment to a user.
 */
class UserPermission
{
    public function __construct(
        public int|string|null $user_id = null,
        public int|string|null $permission_id = null,
        public int|string|null $id = null,
        public ?array $constraints = [],
        public ?Permission $permission = null,
        public ?object $user = null
    ) {
    }

    public static function define(int|string $userId, int|string $permissionId, array $constraints = []): self
    {
        return new self(user_id: $userId, permission_id: $permissionId, constraints: $constraints);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareUserPermission($this->id, $this->user_id, $this->permission_id, $this->constraints);
        $saved = $manager->updateUserPermission($bare);
        $this->id = $saved->id;
        return $this;
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareUserPermission($this->id, $this->user_id, $this->permission_id, $this->constraints);
        $manager->deleteUserPermission($bare);
    }

    public function getPermission(): ?Permission
    {
        if ($this->permission) {
            return $this->permission;
        }

        if (!$this->permission_id) {
            return null;
        }

        /** @var \Vima\Core\Services\PermissionManager $manager */
        $manager = resolve(\Vima\Core\Services\PermissionManager::class);
        $this->permission = $manager->find($this->permission_id);
        return $this->permission;
    }

    public function getUser(): ?object
    {
        if ($this->user) {
            return $this->user;
        }

        if (!$this->user_id) {
            return null;
        }

        /** @var UserRepositoryInterface $repo */
        $repo = resolve(UserRepositoryInterface::class);
        $this->user = $repo->findById($this->user_id);
        return $this->user;
    }
}
