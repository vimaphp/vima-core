<?php

namespace Vima\Core\Entities;

class Permission
{
    public function __construct(
        private string $name,
        private ?string $description = null
    ) {
    }

    public static function define(string $name, ?string $description = null): self
    {
        return new self($name, $description);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
