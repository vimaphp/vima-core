<?php

namespace Vima\Core\Exceptions;

class PermissionNotFoundException extends VimaException
{
    public function __construct(string $permissionName)
    {
        parent::__construct("Permission '{$permissionName}' not found.");
    }
}
