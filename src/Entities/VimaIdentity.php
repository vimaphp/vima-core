<?php

declare(strict_types=1);

namespace Vima\Core\Entities;

class VimaIdentity
{
    public function __construct(
        public int|string $id,
        public array $roles,
    ) {
    }

    public static function define(int|string $id, array $roles): VimaIdentity
    {
        return new self(id: $id, roles: $roles);
    }
}