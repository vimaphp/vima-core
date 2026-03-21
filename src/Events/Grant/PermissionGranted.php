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
use Vima\Core\Entities\Permission;

class PermissionGranted extends Event
{
    public const string NAME = 'vima.grant.permission_granted';

    public function __construct(object|string|int $userId, Permission $permission)
    {
        parent::__construct([
            'user_id' => $userId,
            'permission' => $permission
        ]);
    }
}
