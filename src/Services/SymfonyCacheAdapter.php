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

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Vima\Core\Contracts\CacheInterface;

/**
 * Class SymfonyCacheAdapter
 *
 * Default file-based cache implementation for Vima using Symfony Cache.
 *
 * @package Vima\Core\Services
 */
class SymfonyCacheAdapter implements CacheInterface
{
    private FilesystemAdapter $adapter;

    public function __construct(?string $cacheDir = null)
    {
        $cacheDir ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vima-cache';
        $this->adapter = new FilesystemAdapter('vima', 3600, $cacheDir);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->adapter->getItem($this->sanitizeKey($key));

        if (!$item->isHit()) {
            return $default;
        }

        return $item->get();
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $item = $this->adapter->getItem($this->sanitizeKey($key));
        $item->set($value);

        if ($ttl !== null) {
            $item->expiresAfter($ttl);
        }

        return $this->adapter->save($item);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->adapter->deleteItem($this->sanitizeKey($key));
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    /**
     * Symfony Cache keys must not contain certain characters.
     */
    private function sanitizeKey(string $key): string
    {
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $key);
    }
}
