<?php

/**
 * ISER - Theme Configurator
 *
 * Manages theme configuration CRUD operations, validation,
 * import/export, and reset functionality.
 *
 * @package    ISER\Core\Theme
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 5-6 - Theme System Implementation
 */

namespace ISER\Core\Theme;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

/**
 * ThemeConfigurator Class
 *
 * Handles theme configuration operations including:
 * - Get/Set theme settings
 * - Validate configuration values
 * - Reset to defaults
 * - Export/Import themes
 * - Batch operations
 */
class ThemeConfigurator
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * ThemeManager instance
     */
    private ThemeManager $themeManager;

    /**
     * Setting types
     */
    private const SETTING_TYPES = [
        'color',
        'font',
        'size',
        'url',
        'boolean',
        'text'
    ];

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param ThemeManager $themeManager Theme manager instance
     */
    public function __construct(Database $db, ThemeManager $themeManager)
    {
        $this->db = $db;
        $this->themeManager = $themeManager;
    }

    /**
     * Get theme setting
     *
     * @param string $key Setting key (dot notation supported)
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    public function getSetting(string $key, $default = null)
    {
        try {
            $row = $this->db->selectOne('theme_settings', ['setting_key' => $key]);

            if (!$row) {
                return $default;
            }

            return $this->unserializeValue($row['setting_value'], $row['setting_type']);

        } catch (\Exception $e) {
            Logger::error('Failed to get theme setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Set theme setting
     *
     * @param string $key Setting key (dot notation)
     * @param mixed $value Setting value
     * @param string $category Setting category
     * @return bool True on success
     */
    public function setSetting(string $key, $value, string $category = 'general'): bool
    {
        try {
            // Validate key
            if (empty($key)) {
                throw new \InvalidArgumentException('Setting key cannot be empty');
            }

            // Determine type
            $type = $this->determineType($key, $value);

            // Validate value
            $validation = $this->validateValue($key, $value, $type);
            if (!$validation['valid']) {
                Logger::warning('Invalid theme setting value', [
                    'key' => $key,
                    'error' => $validation['error']
                ]);
                return false;
            }

            // Serialize value
            $serializedValue = $this->serializeValue($value, $type);

            // Check if exists
            $existing = $this->db->selectOne('theme_settings', ['setting_key' => $key]);

            if ($existing) {
                // Update
                $result = $this->db->update('theme_settings', [
                    'setting_value' => $serializedValue,
                    'setting_type' => $type,
                    'category' => $category,
                    'updated_at' => time()
                ], ['setting_key' => $key]);
            } else {
                // Insert
                $result = $this->db->insert('theme_settings', [
                    'setting_key' => $key,
                    'setting_value' => $serializedValue,
                    'setting_type' => $type,
                    'category' => $category,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }

            if ($result) {
                // Clear theme cache
                $this->themeManager->clearCache();

                Logger::info('Theme setting saved', [
                    'key' => $key,
                    'type' => $type,
                    'category' => $category
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to set theme setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Set multiple settings at once
     *
     * @param array $settings Array of key => value pairs
     * @param string $category Category for all settings
     * @return array ['success' => bool, 'saved' => int, 'failed' => int]
     */
    public function setMultiple(array $settings, string $category = 'general'): array
    {
        $saved = 0;
        $failed = 0;

        foreach ($settings as $key => $value) {
            if ($this->setSetting($key, $value, $category)) {
                $saved++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'saved' => $saved,
            'failed' => $failed
        ];
    }

    /**
     * Delete theme setting
     *
     * @param string $key Setting key
     * @return bool True on success
     */
    public function deleteSetting(string $key): bool
    {
        try {
            $result = $this->db->delete('theme_settings', ['setting_key' => $key]);

            if ($result > 0) {
                $this->themeManager->clearCache();

                Logger::info('Theme setting deleted', ['key' => $key]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to delete theme setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reset all theme settings to defaults
     *
     * @return bool True on success
     */
    public function resetToDefaults(): bool
    {
        try {
            // Delete all settings
            $this->db->query("DELETE FROM theme_settings");

            // Clear cache
            $this->themeManager->clearCache();

            Logger::info('Theme settings reset to defaults');

            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to reset theme settings', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reset category to defaults
     *
     * @param string $category Category to reset
     * @return bool True on success
     */
    public function resetCategory(string $category): bool
    {
        try {
            $result = $this->db->delete('theme_settings', ['category' => $category]);

            if ($result > 0) {
                $this->themeManager->clearCache();

                Logger::info('Theme category reset to defaults', ['category' => $category]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Failed to reset theme category', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Export theme configuration
     *
     * @return array Theme configuration
     */
    public function exportTheme(): array
    {
        try {
            $settings = $this->db->select('theme_settings', [], 'category ASC, setting_key ASC');

            $export = [
                'name' => 'Custom Theme',
                'version' => '1.0.0',
                'exported_at' => date('Y-m-d H:i:s'),
                'settings' => []
            ];

            foreach ($settings as $setting) {
                $export['settings'][] = [
                    'key' => $setting['setting_key'],
                    'value' => $setting['setting_value'],
                    'type' => $setting['setting_type'],
                    'category' => $setting['category']
                ];
            }

            Logger::info('Theme exported', ['settings_count' => count($settings)]);

            return $export;

        } catch (\Exception $e) {
            Logger::error('Failed to export theme', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Import theme configuration
     *
     * @param array $themeData Theme data to import
     * @param bool $clearExisting Clear existing settings before import
     * @return array ['success' => bool, 'imported' => int, 'errors' => array]
     */
    public function importTheme(array $themeData, bool $clearExisting = false): array
    {
        $result = [
            'success' => false,
            'imported' => 0,
            'errors' => []
        ];

        try {
            // Validate import data
            if (!isset($themeData['settings']) || !is_array($themeData['settings'])) {
                $result['errors'][] = 'Invalid theme data: missing settings';
                return $result;
            }

            // Clear existing if requested
            if ($clearExisting) {
                $this->resetToDefaults();
            }

            // Import settings
            foreach ($themeData['settings'] as $setting) {
                $key = $setting['key'] ?? null;
                $value = $setting['value'] ?? null;
                $category = $setting['category'] ?? 'general';

                if (!$key) {
                    $result['errors'][] = 'Setting missing key';
                    continue;
                }

                if ($this->setSetting($key, $value, $category)) {
                    $result['imported']++;
                } else {
                    $result['errors'][] = "Failed to import: {$key}";
                }
            }

            $result['success'] = empty($result['errors']);

            Logger::info('Theme imported', [
                'imported' => $result['imported'],
                'errors' => count($result['errors'])
            ]);

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to import theme', [
                'error' => $e->getMessage()
            ]);
            $result['errors'][] = 'Import error: ' . $e->getMessage();
            return $result;
        }
    }

    /**
     * Get all settings by category
     *
     * @param string $category Category name
     * @return array Settings in category
     */
    public function getByCategory(string $category): array
    {
        try {
            $settings = $this->db->select('theme_settings',
                ['category' => $category],
                'setting_key ASC'
            );

            $result = [];

            foreach ($settings as $setting) {
                $result[$setting['setting_key']] = $this->unserializeValue(
                    $setting['setting_value'],
                    $setting['setting_type']
                );
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to get settings by category', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get all categories
     *
     * @return array List of categories
     */
    public function getCategories(): array
    {
        try {
            $result = $this->db->query(
                "SELECT DISTINCT category FROM theme_settings ORDER BY category"
            );

            return array_column($result, 'category');

        } catch (\Exception $e) {
            Logger::error('Failed to get categories', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Validate setting value
     *
     * @param string $key Setting key
     * @param mixed $value Value to validate
     * @param string $type Value type
     * @return array ['valid' => bool, 'error' => string]
     */
    private function validateValue(string $key, $value, string $type): array
    {
        // Color validation
        if ($type === 'color') {
            if (!$this->isValidColor($value)) {
                return ['valid' => false, 'error' => 'Invalid color format'];
            }
        }

        // URL validation
        if ($type === 'url') {
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                return ['valid' => false, 'error' => 'Invalid URL'];
            }
        }

        // Size validation (must have unit)
        if ($type === 'size') {
            if (!preg_match('/^\d+(\.\d+)?(px|rem|em|%|vh|vw)$/', $value)) {
                return ['valid' => false, 'error' => 'Invalid size format'];
            }
        }

        // Font validation
        if ($type === 'font') {
            if (empty($value) || !is_string($value)) {
                return ['valid' => false, 'error' => 'Invalid font'];
            }
        }

        // Boolean validation
        if ($type === 'boolean') {
            if (!is_bool($value) && !in_array($value, ['0', '1', 0, 1, 'true', 'false'], true)) {
                return ['valid' => false, 'error' => 'Invalid boolean'];
            }
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Check if color is valid
     *
     * @param string $color Color to validate
     * @return bool True if valid
     */
    private function isValidColor(string $color): bool
    {
        // Hex color
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return true;
        }

        // Short hex
        if (preg_match('/^#[0-9A-Fa-f]{3}$/', $color)) {
            return true;
        }

        // RGB/RGBA
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)$/', $color)) {
            return true;
        }

        return false;
    }

    /**
     * Determine setting type from key and value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return string Setting type
     */
    private function determineType(string $key, $value): string
    {
        // Check by key
        if (strpos($key, 'color') !== false || strpos($key, 'bg') !== false) {
            return 'color';
        }

        if (strpos($key, 'font') !== false) {
            return 'font';
        }

        if (strpos($key, 'size') !== false || strpos($key, 'width') !== false ||
            strpos($key, 'height') !== false || strpos($key, 'radius') !== false) {
            return 'size';
        }

        if (strpos($key, 'url') !== false || strpos($key, 'logo') !== false ||
            strpos($key, 'favicon') !== false) {
            return 'url';
        }

        // Check by value type
        if (is_bool($value)) {
            return 'boolean';
        }

        return 'text';
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value Value to serialize
     * @param string $type Value type
     * @return string Serialized value
     */
    private function serializeValue($value, string $type): string
    {
        if ($type === 'boolean') {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string)$value;
    }

    /**
     * Unserialize value from storage
     *
     * @param string $value Serialized value
     * @param string $type Value type
     * @return mixed Unserialized value
     */
    private function unserializeValue(string $value, string $type)
    {
        if ($type === 'boolean') {
            return $value === '1' || $value === 'true';
        }

        // Try JSON decode
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    /**
     * Get statistics about theme settings
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        try {
            $total = $this->db->query("SELECT COUNT(*) as count FROM theme_settings")[0]['count'] ?? 0;

            $byCategory = $this->db->query("
                SELECT category, COUNT(*) as count
                FROM theme_settings
                GROUP BY category
            ");

            $byType = $this->db->query("
                SELECT setting_type, COUNT(*) as count
                FROM theme_settings
                GROUP BY setting_type
            ");

            return [
                'total' => $total,
                'by_category' => $byCategory,
                'by_type' => $byType
            ];

        } catch (\Exception $e) {
            Logger::error('Failed to get theme statistics', [
                'error' => $e->getMessage()
            ]);
            return ['total' => 0, 'by_category' => [], 'by_type' => []];
        }
    }
}
