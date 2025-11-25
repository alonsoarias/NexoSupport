<?php
namespace core\cache;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Cache Store Interface
 *
 * Defines the contract that all cache stores must implement.
 * Stores are the actual backends (file, redis, apcu, etc).
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
interface cache_store_interface {

    /**
     * Check if this store is available and can be used
     *
     * @return bool True if store is available
     */
    public static function is_available(): bool;

    /**
     * Check if this store supports the given mode
     *
     * @param int $mode Cache mode
     * @return bool True if supported
     */
    public static function supports_mode(int $mode): bool;

    /**
     * Initialize the store with configuration
     *
     * @param array $definition Cache definition
     * @param string $prefix Key prefix
     * @return void
     */
    public function initialize(array $definition, string $prefix): void;

    /**
     * Get a value from the store
     *
     * @param string $key Cache key
     * @return mixed|false Value or false if not found
     */
    public function get(string $key);

    /**
     * Get multiple values from the store
     *
     * @param array $keys Array of keys
     * @return array Array of key => value pairs
     */
    public function get_many(array $keys): array;

    /**
     * Set a value in the store
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @return bool Success
     */
    public function set(string $key, $value): bool;

    /**
     * Set multiple values in the store
     *
     * @param array $keyvaluearray Array of key => value pairs
     * @return int Number of items set
     */
    public function set_many(array $keyvaluearray): int;

    /**
     * Delete a value from the store
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple values from the store
     *
     * @param array $keys Array of keys
     * @return int Number of items deleted
     */
    public function delete_many(array $keys): int;

    /**
     * Check if a key exists in the store
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Purge all values from the store (for this cache)
     *
     * @return bool Success
     */
    public function purge(): bool;

    /**
     * Get store name
     *
     * @return string Store name
     */
    public function get_name(): string;
}

/**
 * Abstract Cache Store Base Class
 *
 * Provides common functionality for cache stores.
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
abstract class cache_store implements cache_store_interface {

    /** @var array Cache definition */
    protected array $definition = [];

    /** @var string Key prefix */
    protected string $prefix = '';

    /** @var int TTL in seconds (0 = no expiry) */
    protected int $ttl = 0;

    /**
     * Initialize the store
     *
     * @param array $definition Cache definition
     * @param string $prefix Key prefix
     * @return void
     */
    public function initialize(array $definition, string $prefix): void {
        $this->definition = $definition;
        $this->prefix = $prefix;
        $this->ttl = $definition['ttl'] ?? 0;
    }

    /**
     * Build the full cache key
     *
     * @param string $key Base key
     * @return string Full key
     */
    protected function build_key(string $key): string {
        $fullkey = $this->prefix . '/' . $key;

        // Some stores have key length limits
        if (strlen($fullkey) > 200) {
            $fullkey = $this->prefix . '/' . md5($key);
        }

        return $fullkey;
    }

    /**
     * Get multiple values (default implementation)
     *
     * @param array $keys Array of keys
     * @return array Array of key => value pairs
     */
    public function get_many(array $keys): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Set multiple values (default implementation)
     *
     * @param array $keyvaluearray Array of key => value pairs
     * @return int Number of items set
     */
    public function set_many(array $keyvaluearray): int {
        $count = 0;
        foreach ($keyvaluearray as $key => $value) {
            if ($this->set($key, $value)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Delete multiple values (default implementation)
     *
     * @param array $keys Array of keys
     * @return int Number of items deleted
     */
    public function delete_many(array $keys): int {
        $count = 0;
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value Value to serialize
     * @return string Serialized value
     */
    protected function serialize($value): string {
        return serialize($value);
    }

    /**
     * Unserialize value from storage
     *
     * @param string $content Stored content
     * @return mixed Unserialized value
     */
    protected function unserialize(string $content) {
        $data = @unserialize($content);
        if ($data === false && $content !== 'b:0;') {
            return $content; // Return as-is if not serialized
        }
        return $data;
    }
}
