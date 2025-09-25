<?php
declare(strict_types=1);

namespace Vima\Core\Contracts;

interface UserInterface
{
    public function vimaGetId(): string|int;
    public function vimaGetRoles(): array; // role names or IDs
}