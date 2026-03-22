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
use Vima\Core\Contracts\UserRepositoryInterface;
use function Vima\Core\resolve;

class UserRole
{
    public function __construct(
        public string|int $user_id,
        public int|string $role_id,
        public array $context = [],
        public string|int|null $id = null,
    ) {
    }

    public static function define(int|string $user_id, int|string $role_id, array $context = []): UserRole
    {
        return new self(role_id: $role_id, user_id: $user_id, context: $context);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updateUserRole($this);
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $manager->deleteUserRole($this);
    }

    /**
     * Get the role entity.
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
     * Get the resolved user object.
     *
     * @return object|null
     */
    public function getUser(): ?object
    {
        /** @var UserRepositoryInterface $repo */
        $repo = resolve(UserRepositoryInterface::class);
        return $repo->findById($this->user_id);
    }
}