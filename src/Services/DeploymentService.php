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

namespace Vima\Core\Services;

use Vima\Core\Contracts\CacheInterface;

/**
 * Class DeploymentService
 * 
 * Orchestrates optimization and maintenance tasks for production environments.
 *
 * @package Vima\Core\Services
 */
class DeploymentService
{
    public function __construct(
        private RoleManager $roleManager,
        private PolicyRegistry $policyRegistry,
        private CacheInterface $cache
    ) {
    }

    /**
     * Pre-warm all caches to eliminate runtime reflection and recursion.
     *
     * @return array Summary of optimized items.
     */
    public function optimize(): array
    {
        $this->clear();
        
        $stats = [
            'roles' => 0,
            'policies' => 0
        ];

        // 1. Warm Role Inheritance Caches
        $roles = $this->roleManager->all(resolve: false);
        foreach ($roles as $role) {
            $this->roleManager->getRolePermissions($role);
            $stats['roles']++;
        }

        // 2. Warm Policy Attribute Maps
        $policies = $this->policyRegistry->getRegisteredClasses();
        $reflectionMethod = new \ReflectionMethod($this->policyRegistry, 'resolveMethodViaAttributes');

        foreach ($policies as $resource => $policyClass) {
            // Trigger reflection and caching by resolving a dummy permission
            $reflectionMethod->invoke($this->policyRegistry, $policyClass, '__warmup__', null);
            $stats['policies']++;
        }

        return $stats;
    }

    /**
     * Wipe all Vima caches.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }
}
