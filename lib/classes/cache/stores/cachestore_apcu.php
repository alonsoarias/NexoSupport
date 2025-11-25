<?php
namespace core\cache\stores;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\cache\cache;
use core\cache\cache_store;

/**
 * APCu Cache Store
 *
 * Stores cache data in APCu (APC User Cache).
 * Provides very fast in-memory caching, but limited to single server.
 *
 * Best for:
 * - Single server deployments
 * - High-frequency reads
 * - Data that can be rebuilt if lost
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cachestore_apcu extends cache_store {

    /** @var string APCu key prefix */
    protected string $apcu_prefix = '';

    /**
     * Check if APCu is available
     *
     * @return bool True if APCu is available and enabled
     */
    public static function is_available(): bool {
        if (!extension_loaded('apcu')) {
            return false;
        }

        // Check if APCu is enabled
        if (!ini_get('apc.enabled')) {
            return false;
        }

        // If running in CLI, check apc.enable_cli
        if (php_sapi_name() === 'cli' && !ini_get('apc.enable_cli')) {
            return false;
        }

        return function_exists('apcu_fetch');
    }

    /**
     * Check if this store supports the given mode
     *
     * APCu supports APPLICATION mode only (shared between requests on same server)
     *
     * @param int $mode Cache mode
     * @return bool True if supported
     */
    public static function supports_mode(int $mode): bool {
        return $mode === cache::MODE_APPLICATION;
    }

    /**
     * Initialize the APCu store
     *
     * @param array $definition Cache definition
     * @param string $prefix Key prefix
     * @return void
     */
    public function initialize(array $definition, string $prefix): void {
        parent::initialize($definition, $prefix);

        // Create unique prefix including site identifier
        global $CFG;
        $site_id = substr(md5($CFG->wwwroot ?? 'nexosupport'), 0, 8);
        $this->apcu_prefix = 'nxs_' . $site_id . '_' . str_replace('/', '_', $prefix) . '_';
    }

    /**
     * Get store name
     *
     * @return string Store name
     */
    public function get_name(): string {
        return 'apcu';
    }

    /**
     * Build the APCu key
     *
     * @param string $key Cache key
     * @return string Full APCu key
     */
    protected function apcu_key(string $key): string {
        return $this->apcu_prefix . md5($key);
    }

    /**
     * Get a value from APCu
     *
     * @param string $key Cache key
     * @return mixed|false Value or false if not found
     */
    public function get(string $key) {
        $success = false;
        $value = apcu_fetch($this->apcu_key($key), $success);

        if (!$success) {
            return false;
        }

        return $value;
    }

    /**
     * Get multiple values from APCu
     *
     * @param array $keys Array of keys
     * @return array Array of key => value pairs
     */
    public function get_many(array $keys): array {
        if (empty($keys)) {
            return [];
        }

        // Build APCu keys
        $apcu_keys = [];
        $key_map = [];
        foreach ($keys as $key) {
            $apcu_key = $this->apcu_key($key);
            $apcu_keys[] = $apcu_key;
            $key_map[$apcu_key] = $key;
        }

        // Fetch all at once
        $values = apcu_fetch($apcu_keys);
        if (!is_array($values)) {
            $values = [];
        }

        // Map back to original keys
        $result = array_fill_keys($keys, false);
        foreach ($values as $apcu_key => $value) {
            if (isset($key_map[$apcu_key])) {
                $result[$key_map[$apcu_key]] = $value;
            }
        }

        return $result;
    }

    /**
     * Set a value in APCu
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @return bool Success
     */
    public function set(string $key, $value): bool {
        return apcu_store($this->apcu_key($key), $value, $this->ttl);
    }

    /**
     * Set multiple values in APCu
     *
     * @param array $keyvaluearray Array of key => value pairs
     * @return int Number of items set
     */
    public function set_many(array $keyvaluearray): int {
        if (empty($keyvaluearray)) {
            return 0;
        }

        // Build APCu array
        $apcu_data = [];
        foreach ($keyvaluearray as $key => $value) {
            $apcu_data[$this->apcu_key($key)] = $value;
        }

        // Store all at once
        $results = apcu_store($apcu_data, null, $this->ttl);

        // Count failures (returns array of failed keys)
        if (is_array($results)) {
            return count($keyvaluearray) - count($results);
        }

        return $results ? count($keyvaluearray) : 0;
    }

    /**
     * Delete a value from APCu
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool {
        return apcu_delete($this->apcu_key($key));
    }

    /**
     * Delete multiple values from APCu
     *
     * @param array $keys Array of keys
     * @return int Number of items deleted
     */
    public function delete_many(array $keys): int {
        if (empty($keys)) {
            return 0;
        }

        $apcu_keys = array_map([$this, 'apcu_key'], $keys);
        $results = apcu_delete($apcu_keys);

        // Returns array of keys that were deleted
        if (is_array($results)) {
            return count($results);
        }

        return $results ? count($keys) : 0;
    }

    /**
     * Check if a key exists in APCu
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool {
        return apcu_exists($this->apcu_key($key));
    }

    /**
     * Purge all values from this cache
     *
     * Uses APCu iterator to find and delete keys matching our prefix
     *
     * @return bool Success
     */
    public function purge(): bool {
        // Use APCu iterator to find keys with our prefix
        if (class_exists('APCUIterator')) {
            $pattern = '/^' . preg_quote($this->apcu_prefix, '/') . '/';
            $iterator = new \APCUIterator($pattern, APC_ITER_KEY);

            foreach ($iterator as $item) {
                apcu_delete($item['key']);
            }

            return true;
        }

        // Fallback: Clear all user cache (not ideal but works)
        return apcu_clear_cache();
    }

    /**
     * Get APCu stats
     *
     * @return array Statistics
     */
    public function get_stats(): array {
        $info = apcu_cache_info(true);
        $sma = apcu_sma_info(true);

        return [
            'available' => true,
            'num_slots' => $info['num_slots'] ?? 0,
            'num_hits' => $info['num_hits'] ?? 0,
            'num_misses' => $info['num_misses'] ?? 0,
            'num_inserts' => $info['num_inserts'] ?? 0,
            'num_entries' => $info['num_entries'] ?? 0,
            'expunges' => $info['expunges'] ?? 0,
            'memory_size' => $sma['seg_size'] ?? 0,
            'memory_avail' => $sma['avail_mem'] ?? 0,
            'hit_rate' => isset($info['num_hits'], $info['num_misses']) && ($info['num_hits'] + $info['num_misses']) > 0
                ? round($info['num_hits'] / ($info['num_hits'] + $info['num_misses']) * 100, 2) . '%'
                : '0%',
        ];
    }

    /**
     * Get all keys in this cache (for debugging)
     *
     * @return array Array of keys
     */
    public function get_all_keys(): array {
        if (!class_exists('APCUIterator')) {
            return [];
        }

        $keys = [];
        $pattern = '/^' . preg_quote($this->apcu_prefix, '/') . '/';
        $iterator = new \APCUIterator($pattern, APC_ITER_KEY);

        foreach ($iterator as $item) {
            $keys[] = $item['key'];
        }

        return $keys;
    }
}
