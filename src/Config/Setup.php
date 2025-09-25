<?php

declare(strict_types=1);

namespace Vima\Core\Config;

final class Setup
{
    /** @param \Vima\Core\Entities\Role[] $roles */
    /** @param \Vima\Core\Entities\Permission[]  $permissions */
    public function __construct(
        public array $roles = [],
        public array $permissions = [],
    ) {
    }
}
