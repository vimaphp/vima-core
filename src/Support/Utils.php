<?php

namespace Vima\Core\Support;

class Utils
{
    public static function splitPermission(string $permission): array
    {
        if (str_contains($permission, ':')) {
            return explode(':', $permission, 2);
        }

        return [null, $permission];
    }
}