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

/**
 * Class UserDeny
 * 
 * Represents an explicit denial of a permission for a user.
 *
 * @package Vima\Core\Entities
 */
class UserDeny
{
    public function __construct(
        public int|string $user_id,
        public int|string $permission_id,
        public ?int $id = null,
        public ?string $reason = null,
        public ?Permission $permission = null,
    ) {
    }

    public static function define(int|string $user_id, int|string $permission_id, ?string $reason = null): self
    {
        return new self($user_id, $permission_id, reason: $reason);
    }

    public function save(): self
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->updateUserDeny($this);
    }

    public function delete(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $manager->deleteUserDeny($this);
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
