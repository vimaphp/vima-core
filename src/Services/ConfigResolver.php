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

use Exception;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Entities\Permission;
use Vima\Core\Entities\Role;
use Vima\Core\Exceptions\ConfigResolverExcpetion;
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
    protected ?array $allPermissions = null;
    /**
     * @param VimaConfig $config
     */
    public function __construct(protected VimaConfig $config)
    {
        $this->validateConfig($config);
    }

    public function setConfig(VimaConfig $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Validate the structure of the provided configuration.
     *
     * @param VimaConfig $config
     * @return void
     * @throws InvalidConfigException
     */
    public function validateConfig(VimaConfig $config): void
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

            if (!$this->isValidName($perm->name)) {
                throw new InvalidConfigException("Invalid permission name given '{$perm->name}'");
            }
        }

        foreach ($config->setup->roles as $role) {
            if (!$role instanceof Role) {
                throw new InvalidConfigException("Each item in 'roles' must be an instance of Role");
            }

            if (!$this->isValidName($role->name)) {
                throw new InvalidConfigException("Invalid role name given '{$role->name}'");
            }

            foreach ($role->permissions as $p) {
                $n = $p;

                if ($p instanceof Permission) {
                    $n = $p->name;
                }

                if (!$this->isValidName($n)) {
                    throw new InvalidConfigException("Invalid permission name given '{$n}' for role '{$role->name}'");
                }
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
        if ($this->allPermissions) {
            return $this->allPermissions;
        }

        $configPerms = array_map(function (Permission $p) {
            return $p->namespace ? "{$p->namespace}:{$p->name}" : $p->name;
        }, $this->config->setup->permissions);

        $rolePerms = [];

        foreach ($this->config->setup->roles as $role) {
            $rolePerms = [...$rolePerms, ...$role->permissions];
        }

        $strPerms = [];
        foreach ($rolePerms as $perm) {
            $n = $perm;
            if ($perm instanceof Permission) {
                $n = $perm->namespace ? "{$perm->namespace}:{$perm->name}" : $perm->name;
            }

            $strPerms[] = $n;
        }

        $strPerms = array_filter($strPerms, fn($p) => !str_contains($p, '*'));

        $result = [
            ...$configPerms,
            ...$strPerms
        ];

        $this->allPermissions = $result;

        return $result;
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
                'permissions' => $this->resolveRolePermissions($role->permissions, $role),
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
    protected function resolveRolePermissions(array $permissions, Role $role): array
    {
        $all = $this->getPermissions();
        $resolved = [];
        $isSuperAdmin = false;

        foreach ($permissions as $perm) {
            if ($perm instanceof Permission) {
                $name = $perm->name;
                $namespace = $perm->namespace;
            } else {
                $name = $perm;
                $namespace = null;
            }

            // Full namespaced name for search
            $fullName = $namespace ? "{$namespace}:{$name}" : $name;

            // Global wildcard (all permissions from all namespaces)
            if ($name === '*' && !$namespace) {
                $isSuperAdmin = true;
                $resolved = array_merge($resolved, $all);
                continue;
            }

            // Namespaced wildcard (e.g., "blog:*")
            if ($name === '*' && $namespace) {
                $isSuperAdmin = true;
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

                if (empty($matched)) {
                    throw new ConfigResolverExcpetion("No permission match for wildcard '$fullName' was found. Ensure a fully defined permission for it exists either as a " . Permission::class . " object or as a string in a role permissions definition.");
                }

                continue;
            }

            // Exact match - include even if not in $all (implied permissions)
            if (!in_array($fullName, $resolved, true)) {
                $resolved[] = $fullName;
            }
        }

        $result = array_values(array_unique($resolved));

        // check if there any unresolved permissions
        if (count($result) < count($permissions) && !$isSuperAdmin) {
            $fullRoleName = $role->namespace ? "{$role->namespace}:{$role->name}" : $role->name;
            throw new ConfigResolverExcpetion("A count mismatch when resolving permissions for role '{$fullRoleName}'. An issue might be in the format of the permissions given in Setup config");
        }

        return array_filter($result);
    }

    /**
     * Validates role/permission names.
     * Allows: a-z, A-Z, 0-9, _, -, :, *, .
     * Disallows: Whitespace and all other special characters.
     */
    protected function isValidName(string $name)
    {
        // 1. Check for whitespace explicitly (just to be safe)
        if (preg_match('/\s/', $name)) {
            return false;
        }

        $pattern = '/^[a-zA-Z0-9\.\:_\-\*]{1,255}$/';

        return (bool) preg_match($pattern, $name);
    }
}
