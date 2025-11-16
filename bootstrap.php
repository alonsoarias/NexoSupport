<?php
/**
 * NexoSupport - Main Configuration & Bootstrap
 *
 * This file initializes the NexoSupport system and should be included
 * by all entry points (index.php, admin pages, CLI scripts, etc.)
 *
 * DO NOT ACCESS THIS FILE DIRECTLY
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'config.php') {
    http_response_code(403);
    die('Direct access to this file is not allowed.');
}

// Define base directory (if not already defined)
if (!defined('BASE_DIR')) {
    define('BASE_DIR', __DIR__);
}

// Define as internal NexoSupport execution
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
}

// Load environment variables
if (file_exists(BASE_DIR . '/.env')) {
    require_once BASE_DIR . '/vendor/vlucas/phpdotenv/src/Loader/Loader.php';
    require_once BASE_DIR . '/vendor/vlucas/phpdotenv/src/Dotenv.php';

    $dotenv = Dotenv\Dotenv::createImmutable(BASE_DIR);
    $dotenv->load();
}

// Load Composer autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    die('Composer autoloader not found. Please run: composer install');
}
require_once BASE_DIR . '/vendor/autoload.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load system setup (defines constants, helper functions, component system)
// This is auto-loaded by Composer, but we ensure it's loaded here too
if (!function_exists('component_get_path')) {
    require_once BASE_DIR . '/lib/setup.php';
}

// Load RBAC functions (also auto-loaded by Composer)
if (!function_exists('has_capability')) {
    require_once BASE_DIR . '/lib/accesslib.php';
}

// Load compatibility layer
if (!class_exists('RoleManagerCompat')) {
    require_once BASE_DIR . '/lib/compat/roles_compat.php';
}

// Set error reporting based on environment
$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Bogota');

// Initialize core services (optional - can be lazy loaded)
// Database is initialized via Database::getInstance() when needed
// Logger is initialized via Logger::getInstance() when needed

// Configuration is now complete
// Scripts can now safely use all NexoSupport functions and classes
