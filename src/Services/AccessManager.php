<?php

namespace Vima\Core\Services;

use Vima\Core\Config\VimaConfig;
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
        private ?PolicyRegistryInterface $policies = null,
        private readonly ?VimaConfig $config = null,
    ) {
    }

    public function userHasPermission(object $user, string $permission): bool
    {
        $resolver = new UserResolver($this->config);
        $roles = $resolver->resolveRoles($user);

        foreach ($roles as $role) {
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
    public function can(UserInterface $user, string $permission, ...$arguments): bool
    {
        if (!$this->userHasPermission($user, $permission)) {
            return false;
        }

        if (!empty($arguments)) {
            return $this->evaluatePolicy($user, $permission, ...$arguments);
        }

        return true;
    }

    public function authorize(UserInterface $user, string $permission, ...$arguments): void
    {
        if (!$this->can($user, $permission, ...$arguments)) {
            // third param of AccessDeniedException is optional resource description
            throw new AccessDeniedException($user, $permission, $this->config);
        }
    }

    public function evaluatePolicy(UserInterface $user, string $action, ...$arguments): bool
    {
        if (!$this->policies) {
            throw new PolicyNotFoundException($action);
        }

        if (!$this->policies->has($action)) {
            throw new PolicyNotFoundException($action);
        }

        return $this->policies->evaluate($user, $action, ...$arguments);
    }
}
