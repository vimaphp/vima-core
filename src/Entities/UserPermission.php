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
use Vima\Core\Contracts\PermissionRepositoryInterface;
use Vima\Core\Contracts\UserRepositoryInterface;
use function Vima\Core\resolve;

class UserPermission
{
    public function __construct(
        public int|string|null $id = null,
        public int|string $user_id,
        public int|string $permission_id
    ) {
    }

    public static function define(int|string $user_id, int|string $permission_id): UserPermission
    {
        return new self(user_id: $user_id, permission_id: $permission_id, id: null);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updateUserPermission($this);
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $manager->deleteUserPermission($this);
    }

    /**
     * Get the permission entity.
     *
     * @return Permission
     */
    public function getPermission(): Permission
    {
        /** @var PermissionRepositoryInterface $repo */
        $repo = resolve(PermissionRepositoryInterface::class);
        return $repo->findById($this->permission_id);
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