<?php

declare(strict_types=1);

namespace Vima\Core\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Entities\Role;
use Vima\Core\Entities\Permission;
use Vima\Core\Exceptions\UserResolutionException;

final class UserResolver
{
    public function __construct(
        private readonly ?VimaConfig $config = null,
    ) {
    }

    /**
     * Resolve user roles.
     *
     * @param object $user
     * @return int|string
     * @throws UserResolutionException
     */
    public function resolveId(object $user): int|string
    {
        // 1. Check convention: vimaGetRoles
        if (method_exists($user, 'vimaGetId')) {
            return $user->vimaGetId();
        }

        // 2. Check config mapping
        $mappedMethod = $this->config->userMethods->id ?? null;
        if ($mappedMethod && method_exists($user, $mappedMethod)) {
            return $user->{$mappedMethod}();
        }

        // 3. Check composition identity
        if (method_exists($user, 'toVimaIdentity')) {
            $identity = $user->toVimaIdentity();
            if (isset($identity->id)) {
                return $identity->id;
            }
        }

        throw new UserResolutionException('Could not resolve roles for user.');
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
        // 1. Check convention: vimaGetRoles
        if (method_exists($user, 'vimaGetRoles')) {
            return $user->vimaGetRoles();
        }

        // 2. Check config mapping
        $mappedMethod = $this->config->userMethods->roles ?? null;
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
}
