<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


declare(strict_types=1);

namespace Vima\Core\Config;

/**
 * Class Setup
 * 
 * Holds the declarative definitions of roles and permissions for synchronization.
 *
 * @package Vima\Core\Config
 */
final class Setup
{
    /**
     * @param \Vima\Core\Entities\Role[] $roles
     * @param \Vima\Core\Entities\Permission[] $permissions
     */
    public function __construct(
        public array $roles = [],
        public array $permissions = [],
    ) {
    }

    /**
     * Merge roles and permissions from an array into the current setup.
     * 
     * @param array $data ['roles' => Role[], 'permissions' => Permission[]]
     */
    public function merge(array $data): void
    {
        if (isset($data['roles'])) {
            $this->roles = array_merge($this->roles, $data['roles']);
        }

        if (isset($data['permissions'])) {
            $this->permissions = array_merge($this->permissions, $data['permissions']);
        }
    }
}
