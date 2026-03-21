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

namespace Vima\Core\Contracts;

/**
 * Interface SetupProviderInterface
 * 
 * Allows external modules or services to provide roles and permissions
 * to the Vima setup dynamically.
 */
interface SetupProviderInterface
{
    /**
     * Return an array with 'roles' and 'permissions' keys.
     * 
     * [
     *   'roles' => [\Vima\Core\Entities\Role::define(...)],
     *   'permissions' => [\Vima\Core\Entities\Permission::define(...)],
     * ]
     *
     * @return array
     */
    public function get(): array;
}
