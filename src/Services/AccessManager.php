<?php

namespace Vima\Core\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Contracts\AccessManagerInterface;
use Vima\Core\Contracts\PolicyRegistryInterface;
use Vima\Core\Contracts\UserInterface;
use Vima\Core\Contracts\{RoleRepositoryInterface, PermissionRepositoryInterface};
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Exceptions\PolicyNotFoundException;
use Vima\Core\Exceptions\UserNotFoundException;

class AccessManager implements AccessManagerInterface
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermissionRepositoryInterface $permissions,
        private ?PolicyRegistryInterface $policies = null,
        private readonly ?VimaConfig $config = null,
    ) {
    }

    public function userHasPermission(object|string $subjectOrPermission, ?string $permission = null): bool
    {
        if (is_object($subjectOrPermission)) {
            $user = $subjectOrPermission;
        }

        if (is_string($subjectOrPermission)) {
            $user = $this->resolveCurrentUser();
            $permission = $subjectOrPermission;

            if (!$user) {
                throw new UserNotFoundException("User resolution failed. Make sure the resolver for the current user is set in the configuration file or provide a user with request.");
            }
        }

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
    public function can(object|string $subjectOrPermission, mixed $permissionOrArg, mixed ...$arguments): bool
    {

        if (is_string($subjectOrPermission)) {
            $permission = $subjectOrPermission;
            $args = array_merge([$permissionOrArg], $arguments);
            $user = $this->resolveCurrentUser();
        } else {
            $permission = (string) $permissionOrArg; // enforce string here
            $args = $arguments;
        }

        if (!$this->userHasPermission($user, $permission)) {
            return false;
        }

        if (!empty($arguments)) {
            return $this->evaluatePolicy($user, $permission, ...$args);
        }

        return true;
    }

    public function authorize(object|string $subjectOrPermission, mixed $permissionOrArg, mixed ...$arguments): void
    {
        if (!$this->can($subjectOrPermission, $permissionOrArg, ...$arguments)) {
            throw new AccessDeniedException($subjectOrPermission, $permissionOrArg, $this->config);
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

    private function resolveCurrentUser(): ?object
    {
        $user = null;
        if ($this->config->userResolver) {
            $user = ($this->config->userResolver)();
        }

        return $user;
    }
}
