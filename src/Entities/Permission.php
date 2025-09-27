<?php

declare(strict_types=1);

namespace Vima\Core\Entities;

class Permission
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public int|string|null $id = null,
    ) {
    }

    public static function define(string $name, ?string $description = null): self
    {
        return new self(name: $name, description: $description);
    }
}
