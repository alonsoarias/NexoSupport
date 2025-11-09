<?php

/**
 * ISER Authentication System - Environment Manager
 *
 * Handles environment detection, PHP version validation, and extension checks.
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

use RuntimeException;

/**
 * Environment Class
 *
 * Manages environment configuration and system requirements validation.
 */
class Environment
{
    /**
     * Minimum required PHP version
     */
    private const MIN_PHP_VERSION = '8.1.0';

    /**
     * Required PHP extensions
     */
    private const REQUIRED_EXTENSIONS = [
        'pdo',
        'pdo_mysql',
        'json',
        'mbstring',
        'openssl',
        'session',
        'ctype',
        'hash',
    ];

    /**
     * Recommended PHP extensions
     */
    private const RECOMMENDED_EXTENSIONS = [
        'curl',
        'gd',
        'xml',
        'zip',
    ];

    /**
     * Current environment name
     */
    private string $environment;

    /**
     * Debug mode flag
     */
    private bool $debugMode;

    /**
     * Constructor
     *
     * @param string|null $environment Environment name (development, production, testing)
     * @param bool|null $debugMode Debug mode flag
     */
    public function __construct(?string $environment = null, ?bool $debugMode = null)
    {
        $this->environment = $environment ?? $this->detectEnvironment();
        $this->debugMode = $debugMode ?? ($this->environment === 'development');
    }

    /**
     * Detect the current environment
     *
     * @return string Environment name
     */
    private function detectEnvironment(): string
    {
        // Check for environment variable
        $env = getenv('APP_ENV');
        if ($env !== false) {
            return strtolower($env);
        }

        // Check for PHP_SAPI
        if (php_sapi_name() === 'cli') {
            return 'testing';
        }

        // Default to production for safety
        return 'production';
    }

    /**
     * Get the current environment name
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Check if environment is development
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    /**
     * Check if environment is production
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Check if environment is testing
     *
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Validate all system requirements
     *
     * @throws RuntimeException If requirements are not met
     * @return array Validation results
     */
    public function validateRequirements(): array
    {
        $results = [
            'php_version' => $this->validatePhpVersion(),
            'required_extensions' => $this->validateRequiredExtensions(),
            'recommended_extensions' => $this->checkRecommendedExtensions(),
            'writable_directories' => $this->validateWritableDirectories(),
        ];

        return $results;
    }

    /**
     * Validate PHP version
     *
     * @throws RuntimeException If PHP version is too old
     * @return array Validation result
     */
    public function validatePhpVersion(): array
    {
        $currentVersion = PHP_VERSION;
        $isValid = version_compare($currentVersion, self::MIN_PHP_VERSION, '>=');

        if (!$isValid) {
            throw new RuntimeException(
                sprintf(
                    'PHP version %s or higher is required. Current version: %s',
                    self::MIN_PHP_VERSION,
                    $currentVersion
                )
            );
        }

        return [
            'valid' => true,
            'current' => $currentVersion,
            'required' => self::MIN_PHP_VERSION,
        ];
    }

    /**
     * Validate required PHP extensions
     *
     * @throws RuntimeException If required extensions are missing
     * @return array Validation result
     */
    public function validateRequiredExtensions(): array
    {
        $missingExtensions = [];
        $loadedExtensions = [];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            } else {
                $loadedExtensions[] = $extension;
            }
        }

        if (!empty($missingExtensions)) {
            throw new RuntimeException(
                'Missing required PHP extensions: ' . implode(', ', $missingExtensions)
            );
        }

        return [
            'valid' => true,
            'loaded' => $loadedExtensions,
            'missing' => [],
        ];
    }

    /**
     * Check recommended PHP extensions
     *
     * @return array Check result
     */
    public function checkRecommendedExtensions(): array
    {
        $missingExtensions = [];
        $loadedExtensions = [];

        foreach (self::RECOMMENDED_EXTENSIONS as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            } else {
                $loadedExtensions[] = $extension;
            }
        }

        return [
            'loaded' => $loadedExtensions,
            'missing' => $missingExtensions,
        ];
    }

    /**
     * Validate that required directories are writable
     *
     * @return array Validation result
     */
    public function validateWritableDirectories(): array
    {
        $baseDir = dirname(__DIR__, 2);
        $requiredDirs = [
            'var/logs',
            'var/cache',
        ];

        $writableResults = [];
        $nonWritable = [];

        foreach ($requiredDirs as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            $isWritable = is_dir($fullPath) && is_writable($fullPath);

            $writableResults[$dir] = $isWritable;

            if (!$isWritable) {
                $nonWritable[] = $dir;
            }
        }

        return [
            'valid' => empty($nonWritable),
            'writable' => array_keys(array_filter($writableResults)),
            'non_writable' => $nonWritable,
        ];
    }

    /**
     * Get PHP configuration information
     *
     * @return array PHP configuration details
     */
    public function getPhpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'os' => PHP_OS,
            'extensions' => get_loaded_extensions(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * Configure PHP settings for the current environment
     *
     * @return void
     */
    public function configurePhpSettings(): void
    {
        // Error reporting based on environment
        if ($this->isDevelopment() || $this->debugMode) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }

        // Set default timezone
        $timezone = getenv('APP_TIMEZONE') ?: 'UTC';
        date_default_timezone_set($timezone);

        // Set internal encoding
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        // Session settings (basic configuration, JWT will handle sessions)
        // Only set if headers haven't been sent (avoids warnings in testing)
        if (!headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');

            if ($this->isProduction()) {
                ini_set('session.cookie_secure', '1');
            }
        }
    }

    /**
     * Get system information summary
     *
     * @return array System information
     */
    public function getSystemInfo(): array
    {
        return [
            'environment' => $this->environment,
            'debug_mode' => $this->debugMode,
            'php' => $this->getPhpInfo(),
            'requirements' => $this->validateRequirements(),
        ];
    }
}
