<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Models
{
    public function __construct(
        public string $roles,
        public string $permissions,
    ) {
    }
}
