<?php

namespace Vima\Core\Exceptions;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Services\UserResolver;

class AccessDeniedException extends VimaException
{
    public function __construct(object|string $userOrAction, string $action, ?VimaConfig $config = null)
    {
        if (is_string($userOrAction)) {
            $action = $userOrAction;
            $user = $config->userResolver ? ($config->userResolver)() : null;
        } else {
            $action = (string) $action;
        }

        $resolver = new UserResolver($config);
        $id = $user ? $resolver->resolveId($user) : "Unknown User";

        $msg = "Access denied for user [{$id}] on action '{$action}'";

        parent::__construct($msg);
    }
}
