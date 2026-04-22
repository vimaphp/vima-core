<?php

declare(strict_types=1);

namespace Vima\Core\DTOs;

use Vima\Core\Services\AccessManager;
use Vima\Core\Services\UserResolver;
use Vima\Core\Support\Utils;
use function Vima\Core\resolve;
class AccessContext
{
    public function __construct(
        public object $user,
        public string $permission,
        private AccessManager $manager,
        public ?string $namespace = null,
        public array $additionalContext = [],
    ) {
    }

    /**
     * Helpful wrapper so the policy doesn't have to resolve the manager manually
     */
    public function hasRole(string|array $roleName, bool $useAny = true): bool
    {
        $roles = $this->manager->getUserRoles($this->user);
        foreach ($roles as $role) {
            if (is_array($roleName)) {
                foreach ($roleName as $r) {
                    if ($role->validateNamespacedName($r) && $useAny) {
                        return true;
                    }

                    if (!$useAny && !$role->validateNamespacedName($r)) {
                        return false;
                    }
                }
            } else {
                if ($role->validateNamespacedName($roleName) && $useAny) {
                    return true;
                }

                if (!$useAny && !$role->validateNamespacedName($roleName)) {
                    return false;
                }
            }
        }

        if ($useAny) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user has the given role
     * @param string $roleName
     * @return bool
     */
    public function is(string $roleName): bool
    {
        return $this->hasRole($roleName, useAny: true);
    }
    /**
     * Checks if user has any of the provided roles
     * @param array $roleNames
     * @return bool
     */
    public function isAny(array $roleNames): bool
    {
        return $this->hasRole($roleNames, useAny: true);
    }

    /**
     * Checks if user has all the given roles
     * @param array $roleNames
     * @return bool
     */
    public function isAll(array $roleNames): bool
    {
        return $this->hasRole($roleNames, useAny: false);
    }

    /**
     * Checks if user has the designated super admin role. if configured. Super admins bypass all checks.
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->manager->isSuperAdmin($this->user);
    }

    /**
     * A helper to check if the current user owns the resource.
     * Assumes the resource has a user_id or similar.
     * @param mixed $resource
     * @param string $ownerKey The key to check for ownership, defaults to 'user_id'
     * @return bool
     */
    public function owns(mixed $resource, string $ownerKey = 'user_id'): bool
    {
        $userId = $this->resolveId();
        if (is_array($resource)) {
            return ($resource[$ownerKey] ?? null) === $userId;
        }

        if (is_object($resource)) {
            return ($resource->{$ownerKey} ?? null) === $userId;
        }

        return false;
    }

    /**
     * Performs an RBAC check on the user with the given permission
     * @param string $permission Permission name, can be namespaced like 'blog:edit'
     * @return bool
     */
    public function can(string $permission): bool
    {
        [$namespace, $name] = Utils::splitPermission($permission);
        return $this->manager->can($this->user, $name, $namespace);
    }

    public function resolveId(): int|string|null
    {
        /** @var UserResolver $userResolver */
        $userResolver = resolve(UserResolver::class);
        return $userResolver->resolveId($this->user);
    }
}