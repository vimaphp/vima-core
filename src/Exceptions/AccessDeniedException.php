<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Exceptions;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Services\UserResolver;

/**
 * Exception thrown when a user is not authorized to perform an action.
 */
class AccessDeniedException extends VimaException
{
    /**
     * @param string $action
     * @param object|null $user
     * @param UserResolver|null $userResolver
     */
    public function __construct(string $action = 'Unknown', ?object $user = null, ?UserResolver $userResolver = null)
    {
        $id = ($user && $userResolver) ? $userResolver->resolveId($user) : "Unknown User";

        $msg = "Access denied for user [{$id}] on action '{$action}'";

        parent::__construct($msg);
    }

    public static function forPermission(string $permission): self
    {
        return new self($permission);
    }
}
