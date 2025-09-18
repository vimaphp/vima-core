<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

interface UserInterface
{
    public function getId(): string|int;
    public function getRoles(): array; // role names or IDs
}