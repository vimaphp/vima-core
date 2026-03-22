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
use function Vima\Core\resolve;

/**
 * Class VimaIdentity
 * 
 * A compact, read-only representation of a user's authorization state (the "Identity").
 *
 * @package Vima\Core\Entities
 */
class VimaIdentity
{
    /**
     * VimaIdentity constructor.
     *
     * @param int|string $id
     * @param Role[] $roles
     */
    public function __construct(
        public int|string $id,
        public array $roles = [],
    ) {
    }

    /**
     * Static helper to create a VimaIdentity.
     *
     * @param int|string $id
     * @param array $roles
     * @return VimaIdentity
     */
    public static function define(int|string $id, array $roles = []): VimaIdentity
    {
        return new self(id: $id, roles: $roles);
    }

    /**
     * Reconciles the identity's roles and permissions with the persistent store.
     */
    public function save(): void
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        $manager->reconcileAccess((object)['id' => $this->id], $this->roles);
    }

    /**
     * Check if the identity has a specific role by name.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the identity has a specific permission.
     * Delegates to AccessManager for accurate resolution (including direct perms and denials).
     *
     * @param string $permission
     * @param array $context
     * @return bool
     */
    public function can(string $permission, array $context = []): bool
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->can((object)['id' => $this->id], $permission, null, $context);
    }

    /**
     * Check if a permission is explicitly denied for this identity.
     *
     * @param string $permission
     * @return bool
     */
    public function isDenied(string $permission): bool
    {
        /** @var AccessManagerInterface $manager */
        $manager = resolve(AccessManagerInterface::class);
        return $manager->isDenied((object)['id' => $this->id], $permission);
    }
}