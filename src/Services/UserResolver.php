<?php

declare(strict_types=1);

namespace Vima\Core\Services;

use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\UserResolutionException;

final class UserResolver
{
    public function __construct(
        private readonly array $config = [],
    ) {
    }

    /**
     * Resolve user roles.
     *
     * @param object $user
     * @return Role[]
     * @throws UserResolutionException
     */
    public function resolveRoles(object $user): array
    {
        // 1. Check convention: VIMA_getRoles
        if (method_exists($user, 'VIMA_getRoles')) {
            return $user->VIMA_getRoles();
        }

        // 2. Check config mapping
        $mappedMethod = $this->config['user_methods']['roles'] ?? null;
        if ($mappedMethod && method_exists($user, $mappedMethod)) {
            return $user->{$mappedMethod}();
        }

        // 3. Check composition identity
        if (method_exists($user, 'toVimaIdentity')) {
            $identity = $user->toVimaIdentity();
            if (isset($identity->roles)) {
                return $identity->roles;
            }
        }

        throw new UserResolutionException('Could not resolve roles for user.');
    }

    /**
     * Resolve user permissions.
     *
     * @param object $user
     * @return Permission[]
     * @throws UserResolutionException
     */
    public function resolvePermissions(object $user): array
    {
        // 1. Check convention: VIMA_getPermissions
        if (method_exists($user, 'VIMA_getPermissions')) {
            return $user->VIMA_getPermissions();
        }

        // 2. Check config mapping
        $mappedMethod = $this->config['user_methods']['permissions'] ?? null;
        if ($mappedMethod && method_exists($user, $mappedMethod)) {
            return $user->{$mappedMethod}();
        }

        // 3. Check composition identity
        if (method_exists($user, 'toVimaIdentity')) {
            $identity = $user->toVimaIdentity();
            if (isset($identity->permissions)) {
                return $identity->permissions;
            }
        }

        throw new UserResolutionException('Could not resolve permissions for user.');
    }
}
