<?php

namespace Vima\Core\Services;

use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;
use Vima\Core\Exceptions\InvalidConfigException;

class ConfigResolver
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
    }

    /**
     * Validate structure of provided config array.
     */
    protected function validateConfig(array $config): void
    {
        if (!isset($config['permissions']) || !is_array($config['permissions'])) {
            throw new InvalidConfigException("Config must have a 'permissions' array.");
        }

        if (!isset($config['roles']) || !is_array($config['roles'])) {
            throw new InvalidConfigException("Config must have a 'roles' array.");
        }

        foreach ($config['permissions'] as $perm) {
            if (!$perm instanceof Permission) {
                throw new InvalidConfigException("Each item in 'permissions' must be an instance of Permission.");
            }
        }

        foreach ($config['roles'] as $role) {
            if (!$role instanceof Role) {
                throw new InvalidConfigException("Each item in 'roles' must be an instance of Role.");
            }
        }
    }

    /**
     * @return string[] list of permission names
     */
    public function getPermissions(): array
    {
        return array_map(fn(Permission $p) => $p->getName(), $this->config['permissions']);
    }

    /**
     * Get roles with expanded permissions.
     *
     * @return array<string, array{description:?string, permissions:string[]}>
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->config['roles'] as $role) {
            $roles[$role->getName()] = [
                'description' => $role->getDescription(),
                'permissions' => $this->resolveRolePermissions($role->getPermissions()),
            ];
        }

        return $roles;
    }

    /**
     * Expand wildcard patterns into actual permissions.
     *
     * @param Permission[] $permissions
     * @return string[]
     */
    protected function resolveRolePermissions(array $permissions): array
    {
        $all = $this->getPermissions();
        $resolved = [];

        foreach ($permissions as $perm) {
            $name = $perm->getName();

            // Global wildcard
            if ($name === '*') {
                $resolved = array_merge($resolved, $all);
                continue;
            }

            // Wildcard pattern like "users.*"
            if (str_contains($name, '*')) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($name, '/')) . '$/';
                $matched = preg_grep($regex, $all);
                $resolved = array_merge($resolved, $matched);
                continue;
            }

            // Exact match
            if (in_array($name, $all, true)) {
                $resolved[] = $name;
            }
        }

        return array_values(array_unique($resolved));
    }
}
