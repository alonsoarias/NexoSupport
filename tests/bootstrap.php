<?php

/**
 * ISER Authentication System - PHPUnit Bootstrap
 *
 * Bootstrap file for PHPUnit tests.
 *
 * @package    ISER\Tests
 * @category   Tests
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

// Define testing environment
define('ISER_TESTING', true);
define('ISER_BASE_DIR', dirname(__DIR__));
define('NEXOSUPPORT_INTERNAL', true);

// Define system constants needed by classes
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}
define('LIB_DIR', BASE_DIR . '/lib');
define('ADMIN_DIR', BASE_DIR . '/admin');
define('USER_DIR', BASE_DIR . '/user');
define('LOGIN_DIR', BASE_DIR . '/login');
define('THEME_DIR', BASE_DIR . '/theme');
define('REPORT_DIR', BASE_DIR . '/report');
define('AUTH_DIR', BASE_DIR . '/auth');
define('VAR_DIR', BASE_DIR . '/var');
define('PUBLIC_DIR', BASE_DIR . '/public_html');
define('DB_PREFIX', 'test_');

// Load Composer autoloader
require_once ISER_BASE_DIR . '/vendor/autoload.php';

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Create .env file for testing if it doesn't exist
$testEnvFile = ISER_BASE_DIR . '/.env.testing';
$envFile = ISER_BASE_DIR . '/.env';

if (!file_exists($envFile) && !file_exists($testEnvFile)) {
    // Create a minimal .env for testing
    $envContent = <<<ENV
APP_ENV=testing
APP_DEBUG=true
APP_NAME="ISER Auth System - Testing"
APP_URL=http://localhost
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=iser_auth_test
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=test_

JWT_SECRET=test-secret-key-for-phpunit-testing-only
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600
JWT_REFRESH_EXPIRATION=604800

SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SPECIAL=false

LOG_CHANNEL=daily
LOG_LEVEL=debug
LOG_PATH=var/logs/test.log
LOG_MAX_FILES=7
ENV;

    file_put_contents($envFile, $envContent);
    echo "Created .env file for testing\n";
}

// Ensure test directories exist
$testDirs = [
    ISER_BASE_DIR . '/var/logs',
    ISER_BASE_DIR . '/var/cache',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Helper function for tests
function getTestConfig(): array
{
    return [
        'secret' => 'test-secret-key',
        'algorithm' => 'HS256',
        'expiration' => 3600,
        'refresh_expiration' => 604800,
    ];
}

// Helper to get test database config
function getTestDatabaseConfig(): array
{
    return [
        'connection' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => ':memory:', // SQLite for testing
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'test_',
    ];
}

echo "PHPUnit bootstrap loaded successfully\n";
echo "Testing environment initialized\n";
echo "Base directory: " . ISER_BASE_DIR . "\n";
