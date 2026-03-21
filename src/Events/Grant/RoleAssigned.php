<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Events\Grant;

use Vima\Core\Events\Event;
use Vima\Core\Entities\Role;

class RoleAssigned extends Event
{
    public const string NAME = 'vima.grant.role_assigned';

    public function __construct(object|string|int $userId, Role $role)
    {
        parent::__construct([
            'user_id' => $userId,
            'role' => $role
        ]);
    }
}
