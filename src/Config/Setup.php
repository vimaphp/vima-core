<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Setup
{
    public function __construct(
        /** @param \Vima\Core\Entities\Role[] $roles */
        public array $roles = [],
        /** @param \Vima\Core\Entities\Permission[]  $permissions */
        public array $permissions = [],
    ) {
    }
}
