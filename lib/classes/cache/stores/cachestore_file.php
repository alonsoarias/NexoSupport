<?php
namespace core\cache\stores;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\cache\cache;
use core\cache\cache_store;

/**
 * File-based Cache Store
 *
 * Stores cache data in files on the local filesystem.
 * This is the default store for application-level caching.
 *
 * @package    core\cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class cachestore_file extends cache_store {

    /** @var string Cache directory */
    protected string $cachedir = '';

    /**
     * Check if file store is available
     *
     * @return bool Always true for file store
     */
    public static function is_available(): bool {
        global $CFG;
        return is_dir($CFG->cachedir) && is_writable($CFG->cachedir);
    }

    /**
     * Check if this store supports the given mode
     *
     * File store supports APPLICATION mode only
     *
     * @param int $mode Cache mode
     * @return bool True if supported
     */
    public static function supports_mode(int $mode): bool {
        return $mode === cache::MODE_APPLICATION;
    }

    /**
     * Initialize the file store
     *
     * @param array $definition Cache definition
     * @param string $prefix Key prefix
     * @return void
     */
    public function initialize(array $definition, string $prefix): void {
        parent::initialize($definition, $prefix);

        global $CFG;
        $this->cachedir = $CFG->cachedir . '/muc/' . str_replace('/', '_', $prefix);

        if (!is_dir($this->cachedir)) {
            @mkdir($this->cachedir, 0755, true);
        }
    }

    /**
     * Get store name
     *
     * @return string Store name
     */
    public function get_name(): string {
        return 'file';
    }

    /**
     * Get cache file path for a key
     *
     * @param string $key Cache key
     * @return string File path
     */
    protected function get_file_path(string $key): string {
        $fullkey = $this->build_key($key);
        $filename = md5($fullkey) . '.cache';
        return $this->cachedir . '/' . $filename;
    }

    /**
     * Get a value from the file store
     *
     * @param string $key Cache key
     * @return mixed|false Value or false if not found
     */
    public function get(string $key) {
        $file = $this->get_file_path($key);

        if (!file_exists($file)) {
            return false;
        }

        // Check TTL
        if ($this->ttl > 0) {
            $mtime = filemtime($file);
            if (time() - $mtime > $this->ttl) {
                @unlink($file);
                return false;
            }
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return false;
        }

        return $this->unserialize($content);
    }

    /**
     * Set a value in the file store
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @return bool Success
     */
    public function set(string $key, $value): bool {
        $file = $this->get_file_path($key);
        $content = $this->serialize($value);

        // Write atomically using temp file
        $tmpfile = $file . '.' . uniqid('', true) . '.tmp';
        if (file_put_contents($tmpfile, $content, LOCK_EX) === false) {
            return false;
        }

        if (!@rename($tmpfile, $file)) {
            @unlink($tmpfile);
            return false;
        }

        return true;
    }

    /**
     * Delete a value from the file store
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete(string $key): bool {
        $file = $this->get_file_path($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Check if a key exists in the file store
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool {
        $file = $this->get_file_path($key);

        if (!file_exists($file)) {
            return false;
        }

        // Check TTL
        if ($this->ttl > 0) {
            $mtime = filemtime($file);
            if (time() - $mtime > $this->ttl) {
                @unlink($file);
                return false;
            }
        }

        return true;
    }

    /**
     * Purge all values from this cache
     *
     * @return bool Success
     */
    public function purge(): bool {
        if (!is_dir($this->cachedir)) {
            return true;
        }

        $files = glob($this->cachedir . '/*.cache');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * Get cache directory size info
     *
     * @return array Size information
     */
    public function get_stats(): array {
        $count = 0;
        $size = 0;

        if (is_dir($this->cachedir)) {
            $files = glob($this->cachedir . '/*.cache');
            if ($files) {
                $count = count($files);
                foreach ($files as $file) {
                    $size += filesize($file);
                }
            }
        }

        return [
            'files' => $count,
            'size' => $size,
            'directory' => $this->cachedir,
        ];
    }
}
