<?php

declare(strict_types=1);

/**
 * ISER - User Preferences Manager
 *
 * Manages user preferences in the user_preferences table (3FN normalized).
 * Provides a flexible key-value store for user settings.
 *
 * @package    ISER\User
 * @category   User Management
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 6
 */

namespace ISER\User;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

/**
 * PreferencesManager Class
 *
 * Handles CRUD operations for user preferences with type support.
 * Replaces direct access to user_profiles.timezone and user_profiles.locale.
 */
class PreferencesManager
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Default preferences for all users
     */
    private const DEFAULT_PREFERENCES = [
        'locale' => 'es',
        'timezone' => 'America/Bogota',
        'theme' => 'light',
        'notifications_email' => 'true',
        'notifications_browser' => 'true',
    ];

    /**
     * Valid preference types
     */
    private const VALID_TYPES = ['string', 'int', 'bool', 'json'];

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
     * Get a user preference value
     *
     * @param int $userId User ID
     * @param string $key Preference key
     * @param mixed $default Default value if preference doesn't exist
     * @return mixed Preference value or default
     */
    public function get(int $userId, string $key, $default = null)
    {
        try {
            $preference = $this->db->selectOne('user_preferences', [
                'user_id' => $userId,
                'preference_key' => $key
            ]);

            if (!$preference) {
                // Return default from constants or parameter
                return self::DEFAULT_PREFERENCES[$key] ?? $default;
            }

            // Type conversion based on preference_type
            return $this->convertValue(
                $preference['preference_value'],
                $preference['preference_type']
            );

        } catch (\Exception $e) {
            Logger::error('Failed to get preference', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Get all preferences for a user
     *
     * @param int $userId User ID
     * @return array Associative array of preferences [key => value]
     */
    public function getAll(int $userId): array
    {
        try {
            $rows = $this->db->select('user_preferences', ['user_id' => $userId]);

            $preferences = [];
            foreach ($rows as $row) {
                $preferences[$row['preference_key']] = $this->convertValue(
                    $row['preference_value'],
                    $row['preference_type']
                );
            }

            // Merge with defaults for missing preferences
            return array_merge(self::DEFAULT_PREFERENCES, $preferences);

        } catch (\Exception $e) {
            Logger::error('Failed to get all preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return self::DEFAULT_PREFERENCES;
        }
    }

    /**
     * Set a user preference
     *
     * @param int $userId User ID
     * @param string $key Preference key
     * @param mixed $value Preference value
     * @param string $type Value type (string|int|bool|json)
     * @return bool True on success
     */
    public function set(int $userId, string $key, $value, string $type = 'string'): bool
    {
        // Validate type
        if (!in_array($type, self::VALID_TYPES)) {
            Logger::warning('Invalid preference type', [
                'user_id' => $userId,
                'key' => $key,
                'type' => $type
            ]);
            $type = 'string';
        }

        // Convert value to string for storage
        $storedValue = $this->prepareValue($value, $type);

        try {
            // Check if preference exists
            $existing = $this->db->selectOne('user_preferences', [
                'user_id' => $userId,
                'preference_key' => $key
            ]);

            $now = time();

            if ($existing) {
                // Update existing preference
                $result = $this->db->update('user_preferences', [
                    'preference_value' => $storedValue,
                    'preference_type' => $type,
                    'updated_at' => $now
                ], [
                    'user_id' => $userId,
                    'preference_key' => $key
                ]) > 0;

                Logger::debug('Preference updated', [
                    'user_id' => $userId,
                    'key' => $key,
                    'type' => $type
                ]);

                return $result;
            } else {
                // Insert new preference
                $result = $this->db->insert('user_preferences', [
                    'user_id' => $userId,
                    'preference_key' => $key,
                    'preference_value' => $storedValue,
                    'preference_type' => $type,
                    'updated_at' => $now
                ]) !== false;

                Logger::debug('Preference created', [
                    'user_id' => $userId,
                    'key' => $key,
                    'type' => $type
                ]);

                return $result;
            }

        } catch (\Exception $e) {
            Logger::error('Failed to set preference', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Set multiple preferences at once
     *
     * @param int $userId User ID
     * @param array $preferences Associative array [key => value]
     * @param string $defaultType Default type for all values
     * @return int Number of preferences successfully set
     */
    public function setMultiple(int $userId, array $preferences, string $defaultType = 'string'): int
    {
        $successCount = 0;

        foreach ($preferences as $key => $value) {
            // Auto-detect type if not specified
            $type = $this->detectType($value, $defaultType);

            if ($this->set($userId, $key, $value, $type)) {
                $successCount++;
            }
        }

        Logger::info('Bulk preferences update', [
            'user_id' => $userId,
            'total' => count($preferences),
            'success' => $successCount
        ]);

        return $successCount;
    }

    /**
     * Delete a user preference
     *
     * @param int $userId User ID
     * @param string $key Preference key
     * @return bool True on success
     */
    public function delete(int $userId, string $key): bool
    {
        try {
            $result = $this->db->delete('user_preferences', [
                'user_id' => $userId,
                'preference_key' => $key
            ]) > 0;

            if ($result) {
                Logger::debug('Preference deleted', [
                    'user_id' => $userId,
                    'key' => $key
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Logger::error('Failed to delete preference', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete all preferences for a user
     *
     * @param int $userId User ID
     * @return int Number of preferences deleted
     */
    public function deleteAll(int $userId): int
    {
        try {
            $count = $this->db->delete('user_preferences', [
                'user_id' => $userId
            ]);

            Logger::info('All preferences deleted', [
                'user_id' => $userId,
                'count' => $count
            ]);

            return $count;

        } catch (\Exception $e) {
            Logger::error('Failed to delete all preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Check if a preference exists
     *
     * @param int $userId User ID
     * @param string $key Preference key
     * @return bool True if exists
     */
    public function has(int $userId, string $key): bool
    {
        try {
            $preference = $this->db->selectOne('user_preferences', [
                'user_id' => $userId,
                'preference_key' => $key
            ]);

            return $preference !== false;

        } catch (\Exception $e) {
            Logger::error('Failed to check preference existence', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Convert stored value to its proper type
     *
     * @param string $value Stored value
     * @param string $type Preference type
     * @return mixed Converted value
     */
    private function convertValue(string $value, string $type)
    {
        return match($type) {
            'int' => (int)$value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true) ?? $value,
            default => $value,
        };
    }

    /**
     * Prepare value for storage
     *
     * @param mixed $value Value to store
     * @param string $type Value type
     * @return string String representation
     */
    private function prepareValue($value, string $type): string
    {
        return match($type) {
            'int' => (string)(int)$value,
            'bool' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string)$value,
        };
    }

    /**
     * Auto-detect value type
     *
     * @param mixed $value Value to analyze
     * @param string $default Default type if detection fails
     * @return string Detected type
     */
    private function detectType($value, string $default = 'string'): string
    {
        if (is_int($value)) {
            return 'int';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        return $default;
    }

    /**
     * Initialize default preferences for a new user
     *
     * @param int $userId User ID
     * @return int Number of preferences created
     */
    public function initializeDefaults(int $userId): int
    {
        return $this->setMultiple($userId, self::DEFAULT_PREFERENCES);
    }

    /**
     * Get default preferences
     *
     * @return array Default preferences
     */
    public static function getDefaults(): array
    {
        return self::DEFAULT_PREFERENCES;
    }
}
