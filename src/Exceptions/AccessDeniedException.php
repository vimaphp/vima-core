<?php

namespace Vima\Core\Exceptions;

use Vima\Core\Contracts\UserInterface;

class AccessDeniedException extends VimaException
{
    public function __construct(UserInterface $user, string $action, ?string $resource = null)
    {
        $msg = "Access denied for user [{$user->getId()}] on action '{$action}'";
        if ($resource) {
            $msg .= " against resource '{$resource}'";
        }

        parent::__construct($msg);
    }
}
