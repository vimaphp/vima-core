<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Setup
{
    /** @param string[] $roles */
    /** @param string[] $permissions */
    public function __construct(
        public array $roles = [],
        public array $permissions = [],
    ) {
    }
}
