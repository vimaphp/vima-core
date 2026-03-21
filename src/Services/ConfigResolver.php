<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/vimaphp>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Vima\Core\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;
use Vima\Core\Exceptions\InvalidConfigException;

/**
 * Class ConfigResolver
 * 
 * Logic to validate and resolve system configuration, including expanding wildcard permissions.
 *
 * @package Vima\Core\Services
 */
class ConfigResolver
{
    /**
     * @param VimaConfig $config
     */
    public function __construct(protected VimaConfig $config)
    {
        $this->validateConfig($config);
    }

    /**
     * Validate the structure of the provided configuration.
     *
     * @param VimaConfig $config
     * @return void
     * @throws InvalidConfigException
     */
    protected function validateConfig(VimaConfig $config): void
    {
        if (!isset($config->setup->permissions) || !is_array($config->setup->permissions)) {
            throw new InvalidConfigException("Config must have a 'permissions' array.");
        }

        if (!isset($config->setup->roles) || !is_array($config->setup->roles)) {
            throw new InvalidConfigException("Config must have a 'roles' array.");
        }

        foreach ($config->setup->permissions as $perm) {
            if (!$perm instanceof Permission) {
                throw new InvalidConfigException("Each item in 'permissions' must be an instance of Permission.");
            }
        }

        foreach ($config->setup->roles as $role) {
            if (!$role instanceof Role) {
                throw new InvalidConfigException("Each item in 'roles' must be an instance of Role.");
            }
        }
    }

    /**
     * Retrieve all permission names from the configuration.
     * 
     * @return string[] List of permission names.
     */
    public function getPermissions(): array
    {
        return array_map(function (Permission $p) {
            return $p->namespace ? "{$p->namespace}:{$p->name}" : $p->name;
        }, $this->config->setup->permissions);
    }

    /**
     * Get roles with expanded permissions (resolving wildcards).
     *
     * @return array<string, array{description:?string, permissions:string[]}>
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->config->setup->roles as $role) {
            $roles[$role->name] = [
                'description' => $role->description,
                'namespace' => $role->namespace,
                'permissions' => $this->resolveRolePermissions($role->permissions),
                'parents' => $role->parents,
                'children' => $role->children,
            ];
        }

        return $roles;
    }

    /**
     * Expand wildcard patterns into actual permission names.
     *
     * @param Permission[] $permissions
     * @return string[] Resolved list of permission names.
     */
    protected function resolveRolePermissions(array $permissions): array
    {
        $all = $this->getPermissions();
        $resolved = [];

        foreach ($permissions as $perm) {
            $name = $perm->name;
            $namespace = $perm->namespace;

            // Full namespaced name for search
            $fullName = $namespace ? "{$namespace}:{$name}" : $name;

            // Global wildcard (all permissions from all namespaces)
            if ($name === '*' && !$namespace) {
                $resolved = array_merge($resolved, $all);
                continue;
            }

            // Namespaced wildcard (e.g., "blog:*")
            if ($name === '*' && $namespace) {
                $pattern = "/^{$namespace}:.*$/";
                $matched = preg_grep($pattern, $all);
                $resolved = array_merge($resolved, $matched);
                continue;
            }

            // Pattern match (e.g., "users.*" or "blog:posts.*")
            if (str_contains($name, '*')) {
                // If it's a namespaced pattern like "blog:posts.*"
                if (str_contains($name, ':')) {
                    [$pNamespace, $pPattern] = explode(':', $name);
                    $regex = '/^' . preg_quote($pNamespace, '/') . ':' . str_replace('\*', '.*', preg_quote($pPattern, '/')) . '$/';
                } else {
                    // Local pattern adopts permission's namespace
                    $prefix = $namespace ? preg_quote($namespace, '/') . ':' : '';
                    $regex = '/^' . $prefix . str_replace('\*', '.*', preg_quote($name, '/')) . '$/';
                }

                $matched = preg_grep($regex, $all);
                $resolved = array_merge($resolved, $matched);
                continue;
            }

            // Exact match - include even if not in $all (implied permissions)
            if (!in_array($fullName, $resolved, true)) {
                $resolved[] = $fullName;
            }
        }

        return array_values(array_unique($resolved));
    }
}
