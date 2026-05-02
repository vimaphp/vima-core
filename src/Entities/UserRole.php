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
use Vima\Core\Entities\Bare\BareUserRole;
use function Vima\Core\resolve;

/**
 * Class UserRole
 * 
 * Represents the assignment of a role to a user.
 */
class UserRole
{
    public function __construct(
        public int|string|null $user_id = null,
        public int|string|null $role_id = null,
        public int|string|null $id = null,
        public ?array $context = [],
        public ?Role $role = null,
        public ?object $user = null
    ) {
    }

    public static function define(int|string $userId, int|string $roleId, array $context = []): self
    {
        return new self(user_id: $userId, role_id: $roleId, context: $context);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareUserRole($this->id, $this->user_id, $this->role_id, $this->context);
        $saved = $manager->updateUserRole($bare);
        $this->id = $saved->id;
        return $this;
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $bare = new BareUserRole($this->id, $this->user_id, $this->role_id, $this->context);
        $manager->deleteUserRole($bare);
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
