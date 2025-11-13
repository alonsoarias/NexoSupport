<?php

declare(strict_types=1);

/**
 * ISER - Theme Configurator
 *
 * Manages theme configuration from the config table (category='theme')
 * Provides caching and validation of color and font configurations
 *
 * @package ISER\Theme
 * @author ISER Development Team
 * @copyright 2024 ISER
 * @license Proprietary
 */

namespace ISER\Theme;

use ISER\Core\Database\Database;

/**
 * ThemeConfigurator Class
 *
 * Handles theme configuration management with in-memory caching
 * and validation for colors and fonts
 */
class ThemeConfigurator
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * In-memory cache of theme configurations
     */
    private array $cache = [];

    /**
     * Cache loaded flag
     */
    private bool $cacheLoaded = false;

    /**
     * Allowed fonts list
     */
    private array $allowedFonts = [
        'Arial, sans-serif',
        'Helvetica, sans-serif',
        'Georgia, serif',
        'Times New Roman, serif',
        'Courier New, monospace',
        'Trebuchet MS, sans-serif',
        'Verdana, sans-serif',
        'Montserrat, sans-serif',
        'Open Sans, sans-serif',
        'Roboto, sans-serif',
        'Lato, sans-serif',
        'Source Sans Pro, sans-serif',
        'Raleway, sans-serif',
        'Poppins, sans-serif',
        'Ubuntu, sans-serif',
        'Segoe UI, sans-serif',
        'Noto Sans, sans-serif'
    ];

    /**
     * Default color palette
     */
    private array $defaultColors = [
        'primary' => '#2c7be5',
        'secondary' => '#6e84a3',
        'success' => '#00d97e',
        'danger' => '#e63757',
        'warning' => '#f6c343',
        'info' => '#39afd1',
        'light' => '#f9fafd',
        'dark' => '#0b1727'
    ];

    /**
     * Default font configurations
     */
    private array $defaultFonts = [
        'font_heading' => 'Montserrat, sans-serif',
        'font_body' => 'Open Sans, sans-serif',
        'font_mono' => 'Courier New, monospace'
    ];

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Load all theme configurations from database
     *
     * @return void
     */
    private function loadCache(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        try {
            $configs = $this->db->select('config', ['category' => 'theme']);

            foreach ($configs as $config) {
                $key = $config['key'] ?? null;
                $value = $config['value'] ?? null;

                if ($key !== null) {
                    // Try to decode JSON values
                    $decoded = json_decode($value, true);
                    $this->cache[$key] = $decoded !== null ? $decoded : $value;
                }
            }
        } catch (\Exception $e) {
            // Log error and continue with empty cache
            error_log("Error loading theme configuration: " . $e->getMessage());
        }

        $this->cacheLoaded = true;
    }

    /**
     * Get a single configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     */
    public function get(string $key, $default = null)
    {
        $this->loadCache();

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $default;
    }

    /**
     * Set a configuration value
     *
     * Updates in database and cache
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return bool Success status
     */
    public function set(string $key, $value): bool
    {
        // Validate the value
        if (!$this->validateValue($key, $value)) {
            return false;
        }

        try {
            // Prepare value for storage
            $storedValue = is_array($value) ? json_encode($value) : (string)$value;

            // Check if config exists
            $existing = $this->db->selectOne('config', [
                'category' => 'theme',
                'key' => $key
            ]);

            if ($existing) {
                // Update existing
                $result = $this->db->update('config', [
                    'value' => $storedValue,
                    'updated_at' => time()
                ], [
                    'category' => 'theme',
                    'key' => $key
                ]);
            } else {
                // Insert new
                $result = $this->db->insert('config', [
                    'category' => 'theme',
                    'key' => $key,
                    'value' => $storedValue,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }

            if ($result !== false && $result > 0) {
                // Update cache
                $this->cache[$key] = $value;
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log("Error setting theme configuration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all theme configurations
     *
     * @return array All theme configurations
     */
    public function getAll(): array
    {
        $this->loadCache();
        return $this->cache;
    }

    /**
     * Reset theme configuration to defaults
     *
     * Clears all theme configs from database and cache
     *
     * @return bool Success status
     */
    public function reset(): bool
    {
        try {
            // Delete all theme configurations
            $this->db->delete('config', ['category' => 'theme']);

            // Insert default colors
            foreach ($this->defaultColors as $key => $value) {
                $this->db->insert('config', [
                    'category' => 'theme',
                    'key' => $key,
                    'value' => $value,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }

            // Insert default fonts
            foreach ($this->defaultFonts as $key => $value) {
                $this->db->insert('config', [
                    'category' => 'theme',
                    'key' => $key,
                    'value' => $value,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }

            // Clear cache
            $this->cache = array_merge($this->defaultColors, $this->defaultFonts);
            $this->cacheLoaded = true;

            return true;
        } catch (\Exception $e) {
            error_log("Error resetting theme configuration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return bool Validation result
     */
    private function validateValue(string $key, $value): bool
    {
        // Validate colors
        if (strpos($key, 'color') !== false || in_array($key, array_keys($this->defaultColors))) {
            return $this->validateColor((string)$value);
        }

        // Validate fonts
        if (strpos($key, 'font') !== false || in_array($key, array_keys($this->defaultFonts))) {
            return $this->validateFont((string)$value);
        }

        return true;
    }

    /**
     * Validate HEX color value
     *
     * @param string $color Color value to validate
     * @return bool True if valid HEX color
     */
    private function validateColor(string $color): bool
    {
        // Check if valid HEX color
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return true;
        }

        return false;
    }

    /**
     * Validate font from allowed list
     *
     * @param string $font Font name to validate
     * @return bool True if font is in allowed list
     */
    private function validateFont(string $font): bool
    {
        return in_array($font, $this->allowedFonts, true);
    }

    /**
     * Get all allowed fonts
     *
     * @return array List of allowed fonts
     */
    public function getAllowedFonts(): array
    {
        return $this->allowedFonts;
    }

    /**
     * Get default color palette
     *
     * @return array Default colors
     */
    public function getDefaultColors(): array
    {
        return $this->defaultColors;
    }

    /**
     * Get default fonts
     *
     * @return array Default fonts
     */
    public function getDefaultFonts(): array
    {
        return $this->defaultFonts;
    }

    /**
     * Clear the in-memory cache
     *
     * Useful for testing or manual cache invalidation
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->cacheLoaded = false;
    }

    /**
     * Set multiple configuration values at once
     *
     * @param array $configs Key-value pairs to set
     * @return array Success status for each key ['key' => bool]
     */
    public function setMultiple(array $configs): array
    {
        $results = [];

        foreach ($configs as $key => $value) {
            $results[$key] = $this->set($key, $value);
        }

        return $results;
    }

    /**
     * Get configuration by group
     *
     * @param string $group 'colors', 'typography', 'branding', 'layout'
     * @return array Configuration group
     */
    public function getGroup(string $group): array
    {
        $this->loadCache();

        $groupConfig = [];

        // Define group prefixes
        $groupPrefixes = [
            'colors' => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'],
            'typography' => ['font_heading', 'font_body', 'font_mono', 'base_font_size', 'line_height'],
            'branding' => ['logo_url', 'logo_dark_url', 'favicon_url', 'site_name', 'site_tagline'],
            'layout' => ['default_layout', 'sidebar_position', 'sidebar_width', 'navbar_position', 'container_width']
        ];

        if (!isset($groupPrefixes[$group])) {
            return [];
        }

        // Get all keys for this group
        foreach ($groupPrefixes[$group] as $key) {
            if (isset($this->cache[$key])) {
                $groupConfig[$key] = $this->cache[$key];
            }
        }

        return $groupConfig;
    }

    /**
     * Export theme configuration as JSON
     *
     * @return string JSON string
     */
    public function exportConfiguration(): string
    {
        $this->loadCache();

        $export = [
            'theme_export' => [
                'version' => '1.0.0',
                'exported_at' => date('c'),
                'app_version' => '1.0.0'
            ],
            'configuration' => [
                'colors' => $this->getGroup('colors'),
                'typography' => $this->getGroup('typography'),
                'branding' => $this->getGroup('branding'),
                'layout' => $this->getGroup('layout')
            ]
        ];

        return json_encode($export, JSON_PRETTY_PRINT);
    }

    /**
     * Import theme configuration from JSON
     *
     * @param string $json JSON configuration
     * @param bool $validate Validate before applying
     * @return bool Success status
     */
    public function importConfiguration(string $json, bool $validate = true): bool
    {
        try {
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Invalid JSON in theme import: " . json_last_error_msg());
                return false;
            }

            // Validate structure
            if (!isset($data['configuration'])) {
                error_log("Missing 'configuration' key in theme import");
                return false;
            }

            $config = $data['configuration'];

            // Import each group
            $allSuccess = true;

            if (isset($config['colors'])) {
                $results = $this->setMultiple($config['colors']);
                $allSuccess = $allSuccess && !in_array(false, $results, true);
            }

            if (isset($config['typography'])) {
                $results = $this->setMultiple($config['typography']);
                $allSuccess = $allSuccess && !in_array(false, $results, true);
            }

            if (isset($config['branding'])) {
                $results = $this->setMultiple($config['branding']);
                $allSuccess = $allSuccess && !in_array(false, $results, true);
            }

            if (isset($config['layout'])) {
                $results = $this->setMultiple($config['layout']);
                $allSuccess = $allSuccess && !in_array(false, $results, true);
            }

            return $allSuccess;
        } catch (\Exception $e) {
            error_log("Error importing theme configuration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create backup of current configuration
     *
     * @param string $backupName Backup name
     * @return int Backup ID (0 on failure)
     */
    public function createBackup(string $backupName): int
    {
        try {
            $backupData = $this->exportConfiguration();
            $userId = $_SESSION['user_id'] ?? 1;

            $result = $this->db->insert('theme_backups', [
                'backup_name' => $backupName,
                'backup_data' => $backupData,
                'created_by' => $userId,
                'created_at' => time(),
                'is_system_backup' => 0
            ]);

            return $result !== false ? (int)$result : 0;
        } catch (\Exception $e) {
            error_log("Error creating theme backup: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Restore configuration from backup
     *
     * @param int $backupId Backup ID
     * @return bool Success status
     */
    public function restoreBackup(int $backupId): bool
    {
        try {
            $backup = $this->db->selectOne('theme_backups', ['id' => $backupId]);

            if (!$backup) {
                error_log("Backup not found: $backupId");
                return false;
            }

            return $this->importConfiguration($backup['backup_data']);
        } catch (\Exception $e) {
            error_log("Error restoring theme backup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all backups
     *
     * @param int $limit Maximum number of backups to return
     * @return array List of backups
     */
    public function getBackups(int $limit = 20): array
    {
        try {
            return $this->db->select('theme_backups', [], 'created_at DESC', $limit);
        } catch (\Exception $e) {
            error_log("Error getting theme backups: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a backup
     *
     * @param int $backupId Backup ID
     * @return bool Success status
     */
    public function deleteBackup(int $backupId): bool
    {
        try {
            return $this->db->delete('theme_backups', ['id' => $backupId]) > 0;
        } catch (\Exception $e) {
            error_log("Error deleting theme backup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate RGB color
     *
     * @param string $color RGB/RGBA color (e.g., "rgb(255, 0, 0)" or "rgba(255, 0, 0, 0.5)")
     * @return bool Valid status
     */
    private function validateRGBColor(string $color): bool
    {
        // Check RGB format: rgb(r, g, b)
        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
            $r = (int)$matches[1];
            $g = (int)$matches[2];
            $b = (int)$matches[3];

            return $r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255;
        }

        // Check RGBA format: rgba(r, g, b, a)
        if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([0-1]?\.?\d+)\s*\)$/i', $color, $matches)) {
            $r = (int)$matches[1];
            $g = (int)$matches[2];
            $b = (int)$matches[3];
            $a = (float)$matches[4];

            return $r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255 && $a >= 0 && $a <= 1;
        }

        return false;
    }
}
