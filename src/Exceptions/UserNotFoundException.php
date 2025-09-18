<?php

namespace Vima\Core\Exceptions;

class UserNotFoundException extends VimaException
{
    public function __construct(string|int $id)
    {
        parent::__construct("User with ID '{$id}' not found.");
    }
}
