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

/**
 * Class MappingService
 * 
 * Handles persistent mapping between stable slugs and dynamic role/permission names.
 * This ensures that generated mapper classes maintain consistent property names.
 */
class MappingService
{
    private array $mapping = [
        'roles' => [],
        'permissions' => []
    ];

    public function __construct(private string $mappingFilePath)
    {
        $this->load();
    }

    /**
     * Load mapping from file if it exists.
     */
    public function load(): void
    {
        if (file_exists($this->mappingFilePath)) {
            $content = file_get_contents($this->mappingFilePath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $this->mapping = array_merge($this->mapping, $data);
            }
        }
    }

    /**
     * Save mapping to file.
     */
    public function save(): void
    {
        $dir = dirname($this->mappingFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->mappingFilePath,
            json_encode($this->mapping, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Get a stable slug for a name. If it doesn't exist, create one.
     * 
     * @param string $name Original name (e.g. 'posts.create')
     * @param string $type 'roles' or 'permissions'
     * @return string The stable slug (e.g. 'POSTS_CREATE')
     */
    public function getOrRegisterSlug(string $name, string $type): string
    {
        // Check if name already mapped to a slug
        foreach ($this->mapping[$type] as $slug => $mappedName) {
            if ($mappedName === $name) {
                return $slug;
            }
        }

        // Generate new slug
        $slug = $this->generateSlug($name);

        // Ensure slug is unique within this type
        $originalSlug = $slug;
        $counter = 1;
        while (isset($this->mapping[$type][$slug])) {
            $slug = $originalSlug . '_' . $counter++;
        }

        $this->mapping[$type][$slug] = $name;
        return $slug;
    }

    /**
     * Sync the mapping with current names. If a name is missing, it's NOT removed
     * from the mapping to maintain backward compatibility if it's reused.
     */
    public function sync(array $names, string $type): void
    {
        foreach ($names as $name) {
            $this->getOrRegisterSlug($name, $type);
        }
    }

    /**
     * Get all mappings for a type.
     */
    public function all(string $type): array
    {
        return $this->mapping[$type] ?? [];
    }

    /**
     * Generate TypeScript files for roles and permissions.
     */
    public function generateTypeScriptFiles(string $outputDir): void
    {
        $roleGroups = $this->groupByNamespace($this->mapping['roles']);
        foreach ($roleGroups as $namespace => $items) {
            $prefix = $namespace !== '' ? $this->toPascalCase($namespace) : '';
            $this->writeTsFile($outputDir . "/{$prefix}Roles.ts", "{$prefix}Roles", $items);
        }

        $permissionGroups = $this->groupByNamespace($this->mapping['permissions']);
        foreach ($permissionGroups as $namespace => $items) {
            $prefix = $namespace !== '' ? $this->toPascalCase($namespace) : '';
            $this->writeTsFile($outputDir . "/{$prefix}Permissions.ts", "{$prefix}Permissions", $items);
        }
    }

    /**
     * Groups mapping items by namespace. Returns ['' => [slug => name], 'namespaceName' => [slug => name]]
     */
    private function groupByNamespace(array $items): array
    {
        $groups = [];
        foreach ($items as $slug => $actualName) {
            $parts = explode(':', $actualName, 2);
            if (count($parts) === 2) {
                // If the slug starts with the namespace, and we want to remove it for a cleaner UI, we could.
                // But typically, $slug is unique and generated from $actualName.
                $namespace = $parts[0];
                $nameWithoutNamespace = $parts[1];
                $groups[$namespace][$slug] = $nameWithoutNamespace;
            } else {
                $groups[''][$slug] = $actualName;
            }
        }
        return $groups;
    }

    /**
     * Converts a string to PascalCase (e.g. 'client-dashboard' -> 'ClientDashboard')
     */
    private function toPascalCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords($string);
        return str_replace(' ', '', $string);
    }

    /**
     * Internal helper to write a TypeScript file.
     */
    private function writeTsFile(string $filePath, string $name, array $items): void
    {
        ksort($items);
        $content = "/**\n * Generated by Vima Mapping Service. Do not edit manually.\n */\n\n";
        $content .= "export const {$name} = {\n";
        foreach ($items as $slug => $actualName) {
            $content .= "    {$slug}: '{$actualName}',\n";
        }
        $content .= "} as const;\n\n";

        $typeName = rtrim($name, 's');
        $content .= "export type {$typeName} = typeof {$name}[keyof typeof {$name}];\n";

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $content);
    }

    /**
     * Generate a deterministic UPPER_SNAKE_CASE slug from a name.
     */
    private function generateSlug(string $name): string
    {
        // Replace non-alphanumeric with underscores
        $slug = preg_replace('/[^a-zA-Z0-9]/', '_', $name);

        // Convert to uppercase
        $slug = strtoupper($slug);

        // Remove duplicate underscores
        $slug = preg_replace('/_+/', '_', $slug);

        // Trim underscores
        return trim($slug, '_');
    }
}
