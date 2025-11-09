<?php

/**
 * ISER Authentication System - Configuration Manager
 *
 * Manages system configuration from .env or config.php (mutually exclusive).
 *
 * @package    ISER\Core\Config
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Config;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * ConfigManager Class
 *
 * Centralized configuration management supporting both .env and config.php.
 * Only one configuration method is allowed at a time.
 */
class ConfigManager
{
    /**
     * Configuration array
     */
    private array $config = [];

    /**
     * Configuration source (env or file)
     */
    private string $source;

    /**
     * Singleton instance
     */
    private static ?ConfigManager $instance = null;

    /**
     * Base directory path
     */
    private string $baseDir;

    /**
     * Required configuration keys
     */
    private const REQUIRED_KEYS = [
        'APP_ENV',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'JWT_SECRET',
    ];

    /**
     * Private constructor (Singleton pattern)
     *
     * @param string $baseDir Base directory path
     */
    private function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
        $this->loadConfiguration();
    }

    /**
     * Get singleton instance
     *
     * @param string|null $baseDir Base directory path
     * @return ConfigManager
     */
    public static function getInstance(?string $baseDir = null): ConfigManager
    {
        if (self::$instance === null) {
            if ($baseDir === null) {
                $baseDir = dirname(__DIR__, 2);
            }
            self::$instance = new self($baseDir);
        }

        return self::$instance;
    }

    /**
     * Load configuration from available source
     *
     * @throws RuntimeException If no configuration found or both sources exist
     * @return void
     */
    private function loadConfiguration(): void
    {
        $envFile = $this->baseDir . '/.env';
        $configFile = $this->baseDir . '/config.php';

        $hasEnv = file_exists($envFile);
        $hasConfig = file_exists($configFile);

        // Check for mutual exclusivity
        if ($hasEnv && $hasConfig) {
            throw new RuntimeException(
                'Configuration conflict: Both .env and config.php exist. ' .
                'Please use only one configuration method.'
            );
        }

        if (!$hasEnv && !$hasConfig) {
            throw new RuntimeException(
                'No configuration found. Please create .env or config.php file.'
            );
        }

        if ($hasEnv) {
            $this->loadFromEnv($envFile);
            $this->source = 'env';
        } else {
            $this->loadFromFile($configFile);
            $this->source = 'file';
        }

        $this->validateConfiguration();
    }

    /**
     * Load configuration from .env file
     *
     * @param string $envFile Path to .env file
     * @return void
     */
    private function loadFromEnv(string $envFile): void
    {
        $dotenv = Dotenv::createImmutable(dirname($envFile));
        $dotenv->load();

        // Store all environment variables in config array
        foreach ($_ENV as $key => $value) {
            $this->config[$key] = $value;
        }

        // Also check $_SERVER for variables
        foreach ($_SERVER as $key => $value) {
            if (!isset($this->config[$key]) && is_string($value)) {
                $this->config[$key] = $value;
            }
        }
    }

    /**
     * Load configuration from config.php file
     *
     * @param string $configFile Path to config.php file
     * @throws RuntimeException If config.php is invalid
     * @return void
     */
    private function loadFromFile(string $configFile): void
    {
        $config = require $configFile;

        if (!is_array($config)) {
            throw new RuntimeException(
                'config.php must return an array of configuration values'
            );
        }

        $this->config = $config;
    }

    /**
     * Validate that all required configuration keys exist
     *
     * @throws RuntimeException If required keys are missing
     * @return void
     */
    private function validateConfiguration(): void
    {
        $missingKeys = [];

        foreach (self::REQUIRED_KEYS as $key) {
            if (!isset($this->config[$key]) || $this->config[$key] === '') {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new RuntimeException(
                'Missing required configuration keys: ' . implode(', ', $missingKeys)
            );
        }
    }

    /**
     * Get configuration value by key
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if configuration key exists
     *
     * @param string $key Configuration key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get configuration source
     *
     * @return string 'env' or 'file'
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get database configuration
     *
     * @return array Database configuration
     */
    public function getDatabaseConfig(): array
    {
        return [
            'connection' => $this->get('DB_CONNECTION', 'mysql'),
            'host' => $this->get('DB_HOST', 'localhost'),
            'port' => $this->get('DB_PORT', 3306),
            'database' => $this->get('DB_DATABASE'),
            'username' => $this->get('DB_USERNAME'),
            'password' => $this->get('DB_PASSWORD', ''),
            'charset' => $this->get('DB_CHARSET', 'utf8mb4'),
            'collation' => $this->get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => $this->get('DB_PREFIX', 'iser_'),
        ];
    }

    /**
     * Get JWT configuration
     *
     * @return array JWT configuration
     */
    public function getJwtConfig(): array
    {
        return [
            'secret' => $this->get('JWT_SECRET'),
            'algorithm' => $this->get('JWT_ALGORITHM', 'HS256'),
            'expiration' => (int) $this->get('JWT_EXPIRATION', 3600),
            'refresh_expiration' => (int) $this->get('JWT_REFRESH_EXPIRATION', 604800),
        ];
    }

    /**
     * Get mail configuration
     *
     * @return array Mail configuration
     */
    public function getMailConfig(): array
    {
        return [
            'driver' => $this->get('MAIL_DRIVER', 'smtp'),
            'host' => $this->get('MAIL_HOST'),
            'port' => (int) $this->get('MAIL_PORT', 587),
            'username' => $this->get('MAIL_USERNAME'),
            'password' => $this->get('MAIL_PASSWORD'),
            'encryption' => $this->get('MAIL_ENCRYPTION', 'tls'),
            'from_address' => $this->get('MAIL_FROM_ADDRESS'),
            'from_name' => $this->get('MAIL_FROM_NAME'),
        ];
    }

    /**
     * Get logging configuration
     *
     * @return array Logging configuration
     */
    public function getLogConfig(): array
    {
        return [
            'channel' => $this->get('LOG_CHANNEL', 'daily'),
            'level' => $this->get('LOG_LEVEL', 'debug'),
            'path' => $this->baseDir . '/' . $this->get('LOG_PATH', 'var/logs/iser.log'),
            'max_files' => (int) $this->get('LOG_MAX_FILES', 14),
        ];
    }

    /**
     * Get session configuration
     *
     * @return array Session configuration
     */
    public function getSessionConfig(): array
    {
        return [
            'lifetime' => (int) $this->get('SESSION_LIFETIME', 7200),
            'secure' => filter_var($this->get('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN),
            'httponly' => filter_var($this->get('SESSION_HTTPONLY', true), FILTER_VALIDATE_BOOLEAN),
            'samesite' => $this->get('SESSION_SAMESITE', 'Lax'),
        ];
    }

    /**
     * Get password policy configuration
     *
     * @return array Password policy configuration
     */
    public function getPasswordPolicy(): array
    {
        return [
            'min_length' => (int) $this->get('PASSWORD_MIN_LENGTH', 8),
            'require_uppercase' => filter_var($this->get('PASSWORD_REQUIRE_UPPERCASE', true), FILTER_VALIDATE_BOOLEAN),
            'require_lowercase' => filter_var($this->get('PASSWORD_REQUIRE_LOWERCASE', true), FILTER_VALIDATE_BOOLEAN),
            'require_numbers' => filter_var($this->get('PASSWORD_REQUIRE_NUMBERS', true), FILTER_VALIDATE_BOOLEAN),
            'require_special' => filter_var($this->get('PASSWORD_REQUIRE_SPECIAL', true), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Get base directory path
     *
     * @return string
     */
    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * Get path to public directory
     *
     * @return string
     */
    public function getPublicPath(): string
    {
        return $this->baseDir . '/' . $this->get('PUBLIC_PATH', 'public_html');
    }

    /**
     * Get path to var directory
     *
     * @return string
     */
    public function getVarPath(): string
    {
        return $this->baseDir . '/' . $this->get('VAR_PATH', 'var');
    }

    /**
     * Reset singleton instance (useful for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
