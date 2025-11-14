<?php

/**
 * ISER - Plugin Configurator
 *
 * Manages plugin configuration including loading, saving, validating,
 * and resetting plugin settings based on config_schema in manifests.
 *
 * @package    ISER\Plugin
 * @category   Modules
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 4 - Plugin System Completion
 */

namespace ISER\Plugin;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

/**
 * PluginConfigurator Class
 *
 * Handles plugin configuration operations including:
 * - Loading configuration from database
 * - Saving configuration with validation
 * - Validating against config_schema
 * - Resetting to default values
 */
class PluginConfigurator
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * PluginManager instance
     */
    private PluginManager $pluginManager;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param PluginManager $pluginManager PluginManager instance
     */
    public function __construct(Database $db, PluginManager $pluginManager)
    {
        $this->db = $db;
        $this->pluginManager = $pluginManager;
    }

    /**
     * Get plugin configuration
     *
     * Retrieves configuration from database or returns defaults if not set.
     *
     * @param string $slug Plugin slug
     * @return array Configuration values
     */
    public function getConfig(string $slug): array
    {
        try {
            // Get plugin
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin) {
                Logger::warning('Plugin not found for config retrieval', ['slug' => $slug]);
                return [];
            }

            // Get stored configuration
            $configRows = $this->db->select('plugin_config', ['plugin_slug' => $slug]);
            $storedConfig = [];

            foreach ($configRows as $row) {
                $storedConfig[$row['config_key']] = $this->unserializeValue($row['config_value']);
            }

            // Get defaults from schema
            $defaults = $this->getDefaultConfig($slug);

            // Merge stored config with defaults (stored values take precedence)
            $config = array_merge($defaults, $storedConfig);

            Logger::system('Plugin configuration retrieved', [
                'slug' => $slug,
                'keys' => array_keys($config)
            ]);

            return $config;

        } catch (\Exception $e) {
            Logger::error('Failed to get plugin configuration', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Set plugin configuration
     *
     * Validates and saves configuration to database.
     *
     * @param string $slug Plugin slug
     * @param array $config Configuration values
     * @return array [
     *   'success' => bool,
     *   'errors' => array,
     *   'saved' => array
     * ]
     */
    public function setConfig(string $slug, array $config): array
    {
        $result = [
            'success' => false,
            'errors' => [],
            'saved' => []
        ];

        try {
            // Get plugin
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin) {
                $result['errors'][] = 'Plugin not found';
                return $result;
            }

            // Validate configuration
            $validation = $this->validateConfig($slug, $config);
            if (!$validation['valid']) {
                $result['errors'] = $validation['errors'];
                return $result;
            }

            // Get config schema
            $schema = $this->getConfigSchema($slug);
            if (empty($schema)) {
                $result['errors'][] = 'Plugin has no configuration schema';
                return $result;
            }

            // Save each configuration value
            foreach ($config as $key => $value) {
                // Skip keys not in schema
                if (!isset($schema[$key])) {
                    continue;
                }

                $serializedValue = $this->serializeValue($value);

                // Check if config already exists
                $existing = $this->db->selectOne('plugin_config', [
                    'plugin_slug' => $slug,
                    'config_key' => $key
                ]);

                if ($existing) {
                    // Update existing
                    $this->db->update('plugin_config', [
                        'config_value' => $serializedValue,
                        'updated_at' => time()
                    ], [
                        'plugin_slug' => $slug,
                        'config_key' => $key
                    ]);
                } else {
                    // Insert new
                    $this->db->insert('plugin_config', [
                        'plugin_slug' => $slug,
                        'config_key' => $key,
                        'config_value' => $serializedValue,
                        'created_at' => time(),
                        'updated_at' => time()
                    ]);
                }

                $result['saved'][$key] = $value;
            }

            $result['success'] = true;

            Logger::info('Plugin configuration saved', [
                'slug' => $slug,
                'keys' => array_keys($result['saved'])
            ]);

        } catch (\Exception $e) {
            $result['errors'][] = 'Failed to save configuration: ' . $e->getMessage();
            Logger::error('Failed to save plugin configuration', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Validate plugin configuration
     *
     * Validates configuration against config_schema.
     *
     * @param string $slug Plugin slug
     * @param array $config Configuration values
     * @return array [
     *   'valid' => bool,
     *   'errors' => array
     * ]
     */
    public function validateConfig(string $slug, array $config): array
    {
        $result = [
            'valid' => true,
            'errors' => []
        ];

        try {
            $schema = $this->getConfigSchema($slug);
            if (empty($schema)) {
                return $result; // No schema, nothing to validate
            }

            foreach ($schema as $key => $fieldSchema) {
                $value = $config[$key] ?? null;
                $label = $fieldSchema['label'] ?? $key;

                // Check required fields
                if (!empty($fieldSchema['required']) && $this->isEmpty($value)) {
                    $result['valid'] = false;
                    $result['errors'][$key] = "{$label} is required";
                    continue;
                }

                // Skip validation if value is empty and not required
                if ($this->isEmpty($value)) {
                    continue;
                }

                // Type validation
                $type = $fieldSchema['type'] ?? 'string';
                $typeValidation = $this->validateType($value, $type);
                if (!$typeValidation['valid']) {
                    $result['valid'] = false;
                    $result['errors'][$key] = "{$label}: {$typeValidation['error']}";
                    continue;
                }

                // Range validation (min/max)
                if (isset($fieldSchema['min']) || isset($fieldSchema['max'])) {
                    $rangeValidation = $this->validateRange($value, $fieldSchema['min'] ?? null, $fieldSchema['max'] ?? null, $type);
                    if (!$rangeValidation['valid']) {
                        $result['valid'] = false;
                        $result['errors'][$key] = "{$label}: {$rangeValidation['error']}";
                        continue;
                    }
                }

                // Pattern validation (regex)
                if (!empty($fieldSchema['pattern'])) {
                    if (!preg_match($fieldSchema['pattern'], (string)$value)) {
                        $result['valid'] = false;
                        $result['errors'][$key] = "{$label} format is invalid";
                        continue;
                    }
                }

                // Options validation (for select fields)
                if (!empty($fieldSchema['options']) && is_array($fieldSchema['options'])) {
                    if (!in_array($value, $fieldSchema['options'], true)) {
                        $result['valid'] = false;
                        $result['errors'][$key] = "{$label} must be one of: " . implode(', ', $fieldSchema['options']);
                        continue;
                    }
                }
            }

        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['errors']['_general'] = 'Validation error: ' . $e->getMessage();
            Logger::error('Configuration validation failed', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Get default configuration from manifest
     *
     * Extracts default values from config_schema.
     *
     * @param string $slug Plugin slug
     * @return array Default configuration values
     */
    public function getDefaultConfig(string $slug): array
    {
        $defaults = [];

        try {
            $schema = $this->getConfigSchema($slug);

            foreach ($schema as $key => $fieldSchema) {
                if (isset($fieldSchema['default'])) {
                    $defaults[$key] = $fieldSchema['default'];
                }
            }

        } catch (\Exception $e) {
            Logger::warning('Failed to get default config', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
        }

        return $defaults;
    }

    /**
     * Reset configuration to defaults
     *
     * Deletes all stored configuration, reverting to defaults.
     *
     * @param string $slug Plugin slug
     * @return bool True on success
     */
    public function resetConfig(string $slug): bool
    {
        try {
            // Get plugin to verify it exists
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin) {
                Logger::warning('Plugin not found for config reset', ['slug' => $slug]);
                return false;
            }

            // Delete all configuration
            $deleted = $this->db->delete('plugin_config', ['plugin_slug' => $slug]);

            Logger::info('Plugin configuration reset to defaults', [
                'slug' => $slug,
                'deleted_rows' => $deleted
            ]);

            return true;

        } catch (\Exception $e) {
            Logger::error('Failed to reset plugin configuration', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get configuration schema from manifest
     *
     * @param string $slug Plugin slug
     * @return array Configuration schema
     */
    private function getConfigSchema(string $slug): array
    {
        try {
            $plugin = $this->pluginManager->getBySlug($slug);
            if (!$plugin || empty($plugin['manifest'])) {
                return [];
            }

            $manifest = json_decode($plugin['manifest'], true);
            if (!$manifest) {
                return [];
            }

            return $manifest['config_schema'] ?? [];

        } catch (\Exception $e) {
            Logger::warning('Failed to get config schema', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if value is empty
     *
     * @param mixed $value Value to check
     * @return bool True if empty
     */
    private function isEmpty($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_bool($value)) {
            return false; // false is a valid value for booleans
        }

        if (is_array($value)) {
            return empty($value);
        }

        return false;
    }

    /**
     * Validate value type
     *
     * @param mixed $value Value to validate
     * @param string $type Expected type
     * @return array ['valid' => bool, 'error' => string]
     */
    private function validateType($value, string $type): array
    {
        switch ($type) {
            case 'string':
            case 'text':
            case 'textarea':
            case 'email':
            case 'url':
            case 'password':
                if (!is_string($value) && !is_numeric($value)) {
                    return ['valid' => false, 'error' => 'must be a string'];
                }
                break;

            case 'int':
            case 'integer':
            case 'number':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'error' => 'must be a number'];
                }
                break;

            case 'bool':
            case 'boolean':
            case 'checkbox':
                // Accept boolean, 0/1, "0"/"1", true/false strings
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    return ['valid' => false, 'error' => 'must be true or false'];
                }
                break;

            case 'select':
            case 'radio':
                // Will be validated against options separately
                break;

            case 'array':
                if (!is_array($value)) {
                    return ['valid' => false, 'error' => 'must be an array'];
                }
                break;

            default:
                // Unknown type, skip validation
                break;
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Validate value range (min/max)
     *
     * @param mixed $value Value to validate
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @param string $type Value type
     * @return array ['valid' => bool, 'error' => string]
     */
    private function validateRange($value, $min, $max, string $type): array
    {
        if ($type === 'int' || $type === 'integer' || $type === 'number') {
            $numValue = (float)$value;
            if ($min !== null && $numValue < $min) {
                return ['valid' => false, 'error' => "must be at least {$min}"];
            }
            if ($max !== null && $numValue > $max) {
                return ['valid' => false, 'error' => "must be at most {$max}"];
            }
        } elseif ($type === 'string' || $type === 'text' || $type === 'textarea') {
            $length = strlen((string)$value);
            if ($min !== null && $length < $min) {
                return ['valid' => false, 'error' => "must be at least {$min} characters"];
            }
            if ($max !== null && $length > $max) {
                return ['valid' => false, 'error' => "must be at most {$max} characters"];
            }
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value Value to serialize
     * @return string Serialized value
     */
    private function serializeValue($value): string
    {
        if (is_bool($value)) {
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
     * @return mixed Unserialized value
     */
    private function unserializeValue(string $value)
    {
        // Try JSON decode
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Boolean values
        if ($value === '1' || $value === 'true') {
            return true;
        }
        if ($value === '0' || $value === 'false') {
            return false;
        }

        // Numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // Return as string
        return $value;
    }
}
