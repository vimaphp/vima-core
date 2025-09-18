<?php

namespace Vima\Core\Services;

class ConfigSerializer
{
    public function toArray(ConfigResolver $resolver): array
    {
        return [
            'permissions' => $resolver->getPermissions(),
            'roles' => $resolver->getRoles(),
        ];
    }

    public function toJson(ConfigResolver $resolver, int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray($resolver), $flags);
    }
}
