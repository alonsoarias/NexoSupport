<?php
namespace core\cache\stores;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\cache\cache;
use core\cache\cache_store;

/**
 * Redis Cache Store
 *
 * Stores cache data in a Redis server.
 * Provides high-performance distributed caching.
 *
 * Configuration in .env:
 *   CACHE_DRIVER=redis
 *   REDIS_HOST=localhost
 *   REDIS_PORT=6379
 *   REDIS_PASSWORD=
 *   REDIS_DATABASE=0
 *   REDIS_PREFIX=nxs_
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cachestore_redis extends cache_store {

    /** @var \Redis|null Redis connection */
    protected ?\Redis $redis = null;

    /** @var string Redis key prefix */
    protected string $redis_prefix = '';

    /** @var array Connection configuration */
    protected static array $config = [];

    /**
     * Check if Redis extension is available
     *
     * @return bool True if Redis extension is loaded and configured
     */
    public static function is_available(): bool {
        if (!extension_loaded('redis')) {
            return false;
        }

        // Check if Redis is configured
        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '';
        return !empty($host);
    }

    /**
     * Check if this store supports the given mode
     *
     * Redis supports APPLICATION and SESSION modes
     *
     * @param int $mode Cache mode
     * @return bool True if supported
     */
    public static function supports_mode(int $mode): bool {
        return $mode === cache::MODE_APPLICATION || $mode === cache::MODE_SESSION;
    }

    /**
     * Initialize the Redis store
     *
     * @param array $definition Cache definition
     * @param string $prefix Key prefix
     * @return void
     */
    public function initialize(array $definition, string $prefix): void {
        parent::initialize($definition, $prefix);

        // Load configuration from environment
        self::$config = [
            'host' => $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: 'localhost',
            'port' => (int)($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD') ?: null,
            'database' => (int)($_ENV['REDIS_DATABASE'] ?? getenv('REDIS_DATABASE') ?: 0),
            'prefix' => $_ENV['REDIS_PREFIX'] ?? getenv('REDIS_PREFIX') ?: 'nxs_cache_',
            'timeout' => 2.5,
            'read_timeout' => 2.5,
        ];

        $this->redis_prefix = self::$config['prefix'] . str_replace('/', ':', $prefix) . ':';
        $this->connect();
    }

    /**
     * Connect to Redis server
     *
     * @return bool Success
     */
    protected function connect(): bool {
        if ($this->redis !== null) {
            try {
                $this->redis->ping();
                return true;
            } catch (\Exception $e) {
                $this->redis = null;
            }
        }

        try {
            $this->redis = new \Redis();

            $connected = $this->redis->connect(
                self::$config['host'],
                self::$config['port'],
                self::$config['timeout'],
                null,
                0,
                self::$config['read_timeout']
            );

            if (!$connected) {
                $this->redis = null;
                return false;
            }

            // Authenticate if password is set
            if (!empty(self::$config['password'])) {
                $this->redis->auth(self::$config['password']);
            }

            // Select database
            $this->redis->select(self::$config['database']);

            // Set serializer
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

            return true;

        } catch (\Exception $e) {
            debugging('Redis connection failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $this->redis = null;
            return false;
        }
    }

    /**
     * Get store name
     *
     * @return string Store name
     */
    public function get_name(): string {
        return 'redis';
    }

    /**
     * Build the Redis key
     *
     * @param string $key Cache key
     * @return string Full Redis key
     */
    protected function redis_key(string $key): string {
        return $this->redis_prefix . md5($key);
    }

    /**
     * Get a value from Redis
     *
     * @param string $key Cache key
     * @return mixed|false Value or false if not found
     */
    public function get(string $key) {
        if (!$this->connect()) {
            return false;
        }

        try {
            $value = $this->redis->get($this->redis_key($key));
            return $value !== false ? $value : false;
        } catch (\Exception $e) {
            debugging('Redis get failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get multiple values from Redis
     *
     * Uses MGET for efficiency
     *
     * @param array $keys Array of keys
     * @return array Array of key => value pairs
     */
    public function get_many(array $keys): array {
        if (!$this->connect() || empty($keys)) {
            return array_fill_keys($keys, false);
        }

        try {
            $redis_keys = array_map([$this, 'redis_key'], $keys);
            $values = $this->redis->mGet($redis_keys);

            $result = [];
            foreach ($keys as $i => $key) {
                $result[$key] = $values[$i] ?? false;
            }
            return $result;

        } catch (\Exception $e) {
            debugging('Redis mget failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array_fill_keys($keys, false);
        }
    }

    /**
     * Set a value in Redis
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @return bool Success
     */
    public function set(string $key, $value): bool {
        if (!$this->connect()) {
            return false;
        }

        try {
            $redis_key = $this->redis_key($key);

            if ($this->ttl > 0) {
                return $this->redis->setex($redis_key, $this->ttl, $value);
            }

            return $this->redis->set($redis_key, $value);

        } catch (\Exception $e) {
            debugging('Redis set failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Set multiple values in Redis
     *
     * Uses MSET for efficiency
     *
     * @param array $keyvaluearray Array of key => value pairs
     * @return int Number of items set
     */
    public function set_many(array $keyvaluearray): int {
        if (!$this->connect() || empty($keyvaluearray)) {
            return 0;
        }

        try {
            $redis_data = [];
            foreach ($keyvaluearray as $key => $value) {
                $redis_data[$this->redis_key($key)] = $value;
            }

            if ($this->ttl > 0) {
                // With TTL, we need to use pipeline
                $this->redis->multi(\Redis::PIPELINE);
                foreach ($redis_data as $rkey => $value) {
                    $this->redis->setex($rkey, $this->ttl, $value);
                }
                $this->redis->exec();
            } else {
                $this->redis->mSet($redis_data);
            }

            return count($keyvaluearray);

        } catch (\Exception $e) {
            debugging('Redis mset failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Delete a value from Redis
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool {
        if (!$this->connect()) {
            return false;
        }

        try {
            $this->redis->del($this->redis_key($key));
            return true;
        } catch (\Exception $e) {
            debugging('Redis delete failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Delete multiple values from Redis
     *
     * @param array $keys Array of keys
     * @return int Number of items deleted
     */
    public function delete_many(array $keys): int {
        if (!$this->connect() || empty($keys)) {
            return 0;
        }

        try {
            $redis_keys = array_map([$this, 'redis_key'], $keys);
            return (int)$this->redis->del($redis_keys);
        } catch (\Exception $e) {
            debugging('Redis delete_many failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Check if a key exists in Redis
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool {
        if (!$this->connect()) {
            return false;
        }

        try {
            return (bool)$this->redis->exists($this->redis_key($key));
        } catch (\Exception $e) {
            debugging('Redis exists failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Purge all values from this cache
     *
     * Uses SCAN to find and delete keys matching our prefix
     *
     * @return bool Success
     */
    public function purge(): bool {
        if (!$this->connect()) {
            return false;
        }

        try {
            // Use SCAN to find keys with our prefix
            $iterator = null;
            $pattern = $this->redis_prefix . '*';

            while ($keys = $this->redis->scan($iterator, $pattern, 100)) {
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
            }

            return true;

        } catch (\Exception $e) {
            debugging('Redis purge failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get Redis connection stats
     *
     * @return array Statistics
     */
    public function get_stats(): array {
        if (!$this->connect()) {
            return ['connected' => false];
        }

        try {
            $info = $this->redis->info();
            return [
                'connected' => true,
                'server' => self::$config['host'] . ':' . self::$config['port'],
                'database' => self::$config['database'],
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'uptime_days' => $info['uptime_in_days'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Close Redis connection on destruct
     */
    public function __destruct() {
        if ($this->redis !== null) {
            try {
                $this->redis->close();
            } catch (\Exception $e) {
                // Ignore close errors
            }
        }
    }
}
