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
use Vima\Core\Contracts\UserInterface;
use function Vima\Core\resolve;

/**
 * Class User
 * 
 * Represents a user within the Vima system, providing a convenient bridge to their authorization state.
 *
 * @package Vima\Core\Entities
 */
class User implements UserInterface
{
    /**
     * User constructor.
     *
     * @param int|string $id The unique identifier of the user.
     */
    public function __construct(
        public int|string $id
    ) {
    }

    public function vimaGetId(): string|int
    {
        return $this->id;
    }

    public function vimaGetRoles(): array
    {
        return array_map(fn($role) => $role->name, $this->toVimaIdentity()->roles);
    }

    /**
     * Static helper to create a user instance.
     *
     * @param int|string $id
     * @return self
     */
    public static function define(int|string $id): self
    {
        return new self($id);
    }

    /**
     * Resolves and returns the VimaIdentity for this user.
     *
     * @return VimaIdentity
     */
    public function toVimaIdentity(): VimaIdentity
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $roles = $manager->getUserRoles($this);
        
        return VimaIdentity::define($this->id, $roles);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->toVimaIdentity()->hasRole($role);
    }

    /**
     * Check if the user has a specific permission.
     *
     * @param string $permission
     * @param array $context
     * @return bool
     */
    public function can(string $permission, array $context = []): bool
    {
        return $this->toVimaIdentity()->can($permission, $context);
    }
}
