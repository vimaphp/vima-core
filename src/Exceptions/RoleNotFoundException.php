<?php

namespace Vima\Core\Exceptions;

class RoleNotFoundException extends VimaException
{
    public function __construct(string $roleName)
    {
        parent::__construct("Role '{$roleName}' not found.");
    }
}
