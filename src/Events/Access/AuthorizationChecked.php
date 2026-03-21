<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Events\Access;

use Vima\Core\Events\Event;

class AuthorizationChecked extends Event
{
    public const string NAME = 'vima.access.authorization_checked';

    public function __construct(object|string $user, string $permission, bool $result, ?string $namespace = null, array $arguments = [])
    {
        parent::__construct([
            'user' => $user,
            'permission' => $permission,
            'namespace' => $namespace,
            'arguments' => $arguments,
            'result' => $result
        ]);
    }
}
