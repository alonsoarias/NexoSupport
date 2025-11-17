<?php
/**
 * NexoSupport - Simple Cache Class
 *
 * Provides a simple file-based caching system with namespace support
 * and TTL (time-to-live) capabilities
 *
 * @package    ISER\Core\Cache
 * @copyright  2025 ISER
 * @license    Proprietary
 */

declare(strict_types=1);

namespace ISER\Core\Cache;

/**
 * Cache - File-based caching with namespace and TTL support
 *
 * Simple, fast caching for frequently accessed data
 */
class Cache
{
    /**
     * @var string Cache namespace
     */
    private string $namespace;

    /**
     * @var string Cache directory
     */
    private string $cacheDir;

    /**
     * @var int Default time-to-live in seconds (1 hour)
     */
    private int $defaultTtl;

    /**
     * Constructor
     *
     * @param string $namespace Cache namespace (e.g., 'component', 'template')
     * @param int $defaultTtl Default TTL in seconds (default: 3600)
     */
    public function __construct(string $namespace = 'default', int $defaultTtl = 3600)
    {
        $this->namespace = $namespace;
        $this->defaultTtl = $defaultTtl;
        $this->cacheDir = $this->resolveCacheDir();
        $this->ensureCacheDir();
    }

    /**
     * Resolve cache directory path
     *
     * @return string Cache directory path
     */
    private function resolveCacheDir(): string
    {
        $baseDir = dirname(__DIR__, 3) . '/var/cache';

        // Create namespace subdirectory if namespace is provided
        if ($this->namespace !== 'default' && $this->namespace !== '') {
            return $baseDir . '/' . $this->namespace;
        }

        return $baseDir;
    }

    /**
     * Ensure cache directory exists
     *
     * @return void
     */
    private function ensureCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cache file path for a key
     *
     * @param string $key Cache key
     * @return string Full file path
     */
    private function getCacheFilePath(string $key): string
    {
        // Create a safe filename from the key using md5 hash
        $filename = md5($key) . '.cache';
        return $this->cacheDir . '/' . $filename;
    }

    /**
     * Get value from cache
     *
     * Automatically deletes expired entries on retrieval
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filepath = $this->getCacheFilePath($key);

        // File doesn't exist
        if (!file_exists($filepath)) {
            return $default;
        }

        try {
            // Read and unserialize cache data
            $data = unserialize(file_get_contents($filepath), ['allowed_classes' => true]);

            // Check if entry has expired
            if (isset($data['expires']) && $data['expires'] < time()) {
                // Expired - delete and return default
                $this->delete($key);
                return $default;
            }

            // Valid entry - return cached value
            return $data['value'] ?? $default;
        } catch (\Exception $e) {
            // On any error, delete the corrupt cache file and return default
            @unlink($filepath);
            return $default;
        }
    }

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time-to-live in seconds (null = use default)
     * @return bool Success
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $filepath = $this->getCacheFilePath($key);

        // Prepare cache data
        $data = [
            'key' => $key,
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time(),
        ];

        try {
            // Use file locking to prevent race conditions
            $handle = fopen($filepath, 'w');
            if ($handle === false) {
                return false;
            }

            if (!flock($handle, LOCK_EX)) {
                fclose($handle);
                return false;
            }

            $success = fwrite($handle, serialize($data)) !== false;
            flock($handle, LOCK_UN);
            fclose($handle);

            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if key exists and is not expired
     *
     * @param string $key Cache key
     * @return bool True if exists and valid
     */
    public function has(string $key): bool
    {
        $value = $this->get($key, null);
        return $value !== null;
    }

    /**
     * Delete cache entry
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool
    {
        $filepath = $this->getCacheFilePath($key);

        if (file_exists($filepath)) {
            return @unlink($filepath);
        }

        return true;
    }

    /**
     * Clear all cache entries in this namespace
     *
     * @return bool Success
     */
    public function clear(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }

        try {
            $files = glob($this->cacheDir . '/*.cache');
            if ($files === false) {
                return false;
            }

            foreach ($files as $file) {
                @unlink($file);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get or set cache (remember pattern)
     *
     * If key exists and is valid, return cached value.
     * Otherwise, call callback, cache result, and return it.
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value
     * @param int|null $ttl Time-to-live in seconds
     * @return mixed Cached or generated value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        // Check if cached and valid
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Generate new value
        $value = $callback();

        // Cache and return
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * Get cache statistics for this namespace
     *
     * @return array Statistics array
     */
    public function getStats(): array
    {
        $stats = [
            'namespace' => $this->namespace,
            'cache_dir' => $this->cacheDir,
            'entries' => 0,
            'total_size' => 0,
            'expired_entries' => 0,
        ];

        if (!is_dir($this->cacheDir)) {
            return $stats;
        }

        try {
            $files = glob($this->cacheDir . '/*.cache');
            if ($files === false) {
                return $stats;
            }

            foreach ($files as $file) {
                try {
                    $data = unserialize(file_get_contents($file), ['allowed_classes' => true]);
                    $stats['entries']++;
                    $stats['total_size'] += filesize($file);

                    // Check if expired
                    if (isset($data['expires']) && $data['expires'] < time()) {
                        $stats['expired_entries']++;
                    }
                } catch (\Exception $e) {
                    // Skip corrupt files
                }
            }
        } catch (\Exception $e) {
            return $stats;
        }

        return $stats;
    }

    /**
     * Get cache namespace
     *
     * @return string Namespace name
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get default TTL
     *
     * @return int TTL in seconds
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}
