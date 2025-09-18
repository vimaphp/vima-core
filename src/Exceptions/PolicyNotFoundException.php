<?php

namespace Vima\Core\Exceptions;

use RuntimeException;

class PolicyNotFoundException extends RuntimeException
{
    public function __construct(string $permission)
    {
        parent::__construct("No policy registered for permission: {$permission}");
    }
}
