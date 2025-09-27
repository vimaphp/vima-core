<?php

declare(strict_types=1);

namespace Vima\Core\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Exceptions\UserResolutionException;

final class UserResolver
{
    public function __construct(
        private readonly ?VimaConfig $config = null,
    ) {
    }

    /**
     * Resolve user id.
     *
     * @param object $user
     * @return int|string
     * @throws UserResolutionException
     */
    public function resolveId(object $user): int|string
    {
        if (method_exists($user, 'vimaGetId')) {
            return $user->vimaGetId();
        }

        $mappedMethod = $this->config->userMethods->id ?? null;
        if ($mappedMethod && method_exists($user, $mappedMethod)) {
            return $user->{$mappedMethod}();
        }

        if (method_exists($user, 'toVimaIdentity')) {
            $identity = $user->toVimaIdentity();
            if (isset($identity->id)) {
                return $identity->id;
            }
        }

        throw new UserResolutionException('Could not resolve roles for user. Check the documentation on user resolution.');
    }
}
