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
