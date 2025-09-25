<?php

declare(strict_types=1);

namespace Vima\Core\Entities;

final class VimaIdentity
{
    public function __construct(
        public array $roles,
        public array $permissions,
    ) {
    }
}