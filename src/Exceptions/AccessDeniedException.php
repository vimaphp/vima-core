<?php

namespace Vima\Core\Exceptions;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Services\UserResolver;

class AccessDeniedException extends VimaException
{
    public function __construct(object $user, string $action, ?VimaConfig $config = null)
    {
        $resolver = new UserResolver($config);

        $id = $resolver->resolveId($user);

        $msg = "Access denied for user [{$id}] on action '{$action}'";

        parent::__construct($msg);
    }
}
