<?php

namespace Vima\Core\Services;

use Vima\Core\Contracts\CacheInterface;

/**
 * Simple in‑memory cache implementation used for benchmarking.
 *
 * It stores values in a static array for the lifetime of the request and
 * respects TTL (time‑to‑live) values. This is sufficient for the benchmark
 * scenario where we want to measure the benefit of caching without involving
 * external services like Redis or the filesystem.
 */
class InMemoryCache implements CacheInterface
{
    /** @var array<string, array{value:mixed, expiresAt?:int}> */
    private static array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset(self::$store[$key])) {
            return $default;
        }

        $entry = self::$store[$key];
        if (isset($entry['expiresAt']) && $entry['expiresAt'] < time()) {
            // Expired – remove and return default
            unset(self::$store[$key]);
            return $default;
        }

        return $entry['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $expiresAt = $ttl !== null ? time() + $ttl : null;
        self::$store[$key] = ['value' => $value, 'expiresAt' => $expiresAt];
        return true;
    }

    public function delete(string $key): bool
    {
        unset(self::$store[$key]);
        return true;
    }

    public function clear(): bool
    {
        self::$store = [];
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return isset(self::$store[$key]);
    }
}
