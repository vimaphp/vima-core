<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


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
