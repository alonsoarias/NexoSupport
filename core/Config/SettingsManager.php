<?php
/**
 * ISER - Dynamic Settings Manager
 *
 * Manages dynamic system settings stored in database with caching.
 * Different from ConfigManager which handles static configuration from .env
 *
 * @package    ISER\Core\Config
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Core\Config;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

class SettingsManager
{
    private Database $db;
    private array $cache = [];
    private bool $cacheLoaded = false;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get setting value
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @param mixed $default Default value if not found
     * @return mixed Setting value or default
     */
    public function get(string $name, string $plugin = 'core', mixed $default = null): mixed
    {
        // Ensure cache is loaded
        if (!$this->cacheLoaded) {
            $this->loadAllSettings();
        }

        $key = $this->getCacheKey($plugin, $name);

        return $this->cache[$key] ?? $default;
    }

    /**
     * Set setting value
     *
     * @param string $name Setting name
     * @param mixed $value Setting value
     * @param string $plugin Plugin name (default: 'core')
     * @return bool True on success
     */
    public function set(string $name, mixed $value, string $plugin = 'core'): bool
    {
        $now = time();
        $key = $this->getCacheKey($plugin, $name);

        // Convert value to string for storage
        $valueStr = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;

        // Check if setting exists
        $existing = $this->db->selectOne('config', [
            'plugin' => $plugin,
            'name' => $name
        ]);

        if ($existing) {
            // Update existing
            $result = $this->db->update('config', [
                'value' => $valueStr,
                'timemodified' => $now
            ], [
                'plugin' => $plugin,
                'name' => $name
            ]);
        } else {
            // Insert new
            $result = $this->db->insert('config', [
                'plugin' => $plugin,
                'name' => $name,
                'value' => $valueStr,
                'timecreated' => $now,
                'timemodified' => $now
            ]);
        }

        if ($result !== false) {
            // Update cache
            $this->cache[$key] = $value;

            Logger::auth('Setting updated', [
                'plugin' => $plugin,
                'name' => $name
            ]);

            return true;
        }

        return false;
    }

    /**
     * Delete setting
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @return bool True on success
     */
    public function delete(string $name, string $plugin = 'core'): bool
    {
        $key = $this->getCacheKey($plugin, $name);

        $result = $this->db->delete('config', [
            'plugin' => $plugin,
            'name' => $name
        ]);

        if ($result > 0) {
            // Remove from cache
            unset($this->cache[$key]);

            Logger::auth('Setting deleted', [
                'plugin' => $plugin,
                'name' => $name
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get all settings for a plugin
     *
     * @param string $plugin Plugin name
     * @return array Associative array of settings
     */
    public function getPluginSettings(string $plugin): array
    {
        $settings = $this->db->select('config', ['plugin' => $plugin]);
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting['name']] = $this->parseValue($setting['value']);
        }

        return $result;
    }

    /**
     * Set multiple settings at once
     *
     * @param array $settings Associative array of name => value
     * @param string $plugin Plugin name (default: 'core')
     * @return bool True if all successful
     */
    public function setMultiple(array $settings, string $plugin = 'core'): bool
    {
        $success = true;

        foreach ($settings as $name => $value) {
            if (!$this->set($name, $value, $plugin)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if setting exists
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @return bool True if exists
     */
    public function exists(string $name, string $plugin = 'core'): bool
    {
        if (!$this->cacheLoaded) {
            $this->loadAllSettings();
        }

        $key = $this->getCacheKey($plugin, $name);
        return isset($this->cache[$key]);
    }

    /**
     * Get all settings
     *
     * @return array All settings grouped by plugin
     */
    public function getAllSettings(): array
    {
        if (!$this->cacheLoaded) {
            $this->loadAllSettings();
        }

        $grouped = [];

        foreach ($this->cache as $key => $value) {
            [$plugin, $name] = explode(':', $key, 2);
            if (!isset($grouped[$plugin])) {
                $grouped[$plugin] = [];
            }
            $grouped[$plugin][$name] = $value;
        }

        return $grouped;
    }

    /**
     * Clear settings cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->cacheLoaded = false;
    }

    /**
     * Reload all settings from database
     *
     * @return void
     */
    public function reload(): void
    {
        $this->clearCache();
        $this->loadAllSettings();
    }

    /**
     * Load all settings into cache
     *
     * @return void
     */
    private function loadAllSettings(): void
    {
        $settings = $this->db->select('config');

        foreach ($settings as $setting) {
            $key = $this->getCacheKey($setting['plugin'], $setting['name']);
            $this->cache[$key] = $this->parseValue($setting['value']);
        }

        $this->cacheLoaded = true;
    }

    /**
     * Get cache key for plugin/name combination
     *
     * @param string $plugin Plugin name
     * @param string $name Setting name
     * @return string Cache key
     */
    private function getCacheKey(string $plugin, string $name): string
    {
        return $plugin . ':' . $name;
    }

    /**
     * Parse setting value from string
     *
     * @param string $value String value from database
     * @return mixed Parsed value
     */
    private function parseValue(string $value): mixed
    {
        // Try to decode JSON
        if (str_starts_with($value, '{') || str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Check for boolean strings
        if ($value === 'true' || $value === '1') {
            return true;
        }
        if ($value === 'false' || $value === '0') {
            return false;
        }

        // Check for numeric values
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }

    /**
     * Get setting with type casting
     *
     * @param string $name Setting name
     * @param string $type Expected type (string, int, bool, array, etc.)
     * @param string $plugin Plugin name (default: 'core')
     * @param mixed $default Default value
     * @return mixed Casted value
     */
    public function getTyped(string $name, string $type, string $plugin = 'core', mixed $default = null): mixed
    {
        $value = $this->get($name, $plugin, $default);

        return match ($type) {
            'int', 'integer' => (int)$value,
            'bool', 'boolean' => (bool)$value,
            'float', 'double' => (float)$value,
            'string' => (string)$value,
            'array' => is_array($value) ? $value : [],
            default => $value
        };
    }

    /**
     * Get setting as integer
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @param int $default Default value
     * @return int Setting value
     */
    public function getInt(string $name, string $plugin = 'core', int $default = 0): int
    {
        return $this->getTyped($name, 'int', $plugin, $default);
    }

    /**
     * Get setting as boolean
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @param bool $default Default value
     * @return bool Setting value
     */
    public function getBool(string $name, string $plugin = 'core', bool $default = false): bool
    {
        return $this->getTyped($name, 'bool', $plugin, $default);
    }

    /**
     * Get setting as string
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @param string $default Default value
     * @return string Setting value
     */
    public function getString(string $name, string $plugin = 'core', string $default = ''): string
    {
        return $this->getTyped($name, 'string', $plugin, $default);
    }

    /**
     * Get setting as array
     *
     * @param string $name Setting name
     * @param string $plugin Plugin name (default: 'core')
     * @param array $default Default value
     * @return array Setting value
     */
    public function getArray(string $name, string $plugin = 'core', array $default = []): array
    {
        return $this->getTyped($name, 'array', $plugin, $default);
    }

    /**
     * Import settings from array
     *
     * @param array $settings Setting array
     * @param bool $overwrite Overwrite existing settings
     * @return int Number of imported settings
     */
    public function importSettings(array $settings, bool $overwrite = false): int
    {
        $count = 0;

        foreach ($settings as $pluginName => $pluginSettings) {
            if (!is_array($pluginSettings)) {
                continue;
            }

            foreach ($pluginSettings as $name => $value) {
                if ($overwrite || !$this->exists($name, $pluginName)) {
                    if ($this->set($name, $value, $pluginName)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Export settings to array
     *
     * @param string|null $plugin Plugin name (null for all)
     * @return array Setting array
     */
    public function exportSettings(?string $plugin = null): array
    {
        if ($plugin !== null) {
            return [$plugin => $this->getPluginSettings($plugin)];
        }

        return $this->getAllSettings();
    }
}
