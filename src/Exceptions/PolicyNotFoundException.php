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

use RuntimeException;

class PolicyNotFoundException extends RuntimeException
{
    public function __construct(string $permission)
    {
        parent::__construct("No policy registered for permission: {$permission}");
    }
}
