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

namespace Vima\Core\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Exceptions\UserResolutionException;

/**
 * Class UserResolver
 * 
 * Responsible for extracting a unique identifier from various user object implementations.
 *
 * @package Vima\Core\Services
 */
final class UserResolver
{
    /**
     * @param VimaConfig|null $config
     */
    public function __construct(
        private readonly ?VimaConfig $config = null,
    ) {
    }

    /**
     * Resolves a unique ID from the given user object.
     * 
     * It tries several methods:
     * 1. A dedicated `vimaGetId()` method.
     * 2. A method configured in VimaConfig.
     * 3. A `toVimaIdentity()` method that returns an object with an `id`.
     *
     * @param object $user
     * @return int|string
     * @throws UserResolutionException If the ID cannot be resolved.
     */
    public function resolveId(object $user): int|string
    {
        // 1. Custom resolver from config takes precedence
        if ($this->config?->userResolver !== null) {
            return ($this->config->userResolver)($user);
        }

        // 2. Fallback to standard methods
        if (method_exists($user, 'vimaGetId')) {
            return $user->vimaGetId();
        }

        $mappedMethod = $this->config?->userMethods?->id ?? null;
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

    public function getIdResolver(): \Closure
    {
        return $this->config?->userResolver ?? function (object $user) {
            return $this->resolveId($user);
        };
    }
}
