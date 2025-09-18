<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\UserInterface;
use Vima\Core\Contracts\{RoleRepositoryInterface, PermissionRepositoryInterface};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Exceptions\PolicyNotFoundException;

class AccessManager implements AccessManagerInterface
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions,
        private ?PolicyRegistryInterface $policies = null
    ) {
    }

    public function userHasPermission(UserInterface $user, string $permission): bool
    {
        foreach ($user->getRoles() as $role) {
            foreach ($role->getPermissions() as $perm) {
                if ($perm->getName() === $permission) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Combined RBAC + ABAC check.
     */
    public function can(UserInterface $user, string $permission, mixed $resource = null): bool
    {
        if (!$this->userHasPermission($user, $permission)) {
            return false;
        }

        if ($resource !== null) {
            return $this->evaluatePolicy($user, $permission, $resource);
        }

        return true;
    }

    public function authorize(UserInterface $user, string $permission, mixed $resource = null): void
    {
        if (!$this->can($user, $permission, $resource)) {
            // third param of AccessDeniedException is optional resource description
            throw new AccessDeniedException($user, $permission, is_object($resource) ? get_class($resource) : (string) $resource);
        }
    }

    public function evaluatePolicy(UserInterface $user, string $action, mixed $resource): bool
    {
        if (!$this->policies) {
            throw new PolicyNotFoundException($action);
        }

        if (!$this->policies->has($action)) {
            throw new PolicyNotFoundException($action);
        }

        return $this->policies->evaluate($user, $action, $resource);
    }
}
