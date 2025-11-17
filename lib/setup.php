<?php
/**
 * NexoSupport - System Setup and Initialization
 *
 * This file defines global constants, loads core functions,
 * and initializes the Frankenstyle environment.
 *
 * @package    NexoSupport
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// ========================================
// SYSTEM CONSTANTS
// ========================================

/** System version */
define('NEXOSUPPORT_VERSION', '1.0.0');

/** System name */
define('NEXOSUPPORT_NAME', 'NexoSupport');

/** Base directory (already defined in index.php, but check) */
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

/** Directory paths */
define('LIB_DIR', BASE_DIR . '/lib');
define('ADMIN_DIR', BASE_DIR . '/admin');
define('USER_DIR', BASE_DIR . '/user');
define('LOGIN_DIR', BASE_DIR . '/login');
define('THEME_DIR', BASE_DIR . '/theme');
define('REPORT_DIR', BASE_DIR . '/report');
define('AUTH_DIR', BASE_DIR . '/auth');
define('VAR_DIR', BASE_DIR . '/var');
define('PUBLIC_DIR', BASE_DIR . '/public_html');

/** Database table prefix (loaded from .env, default fallback) */
if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', getenv('DB_PREFIX') ?: 'iser_');
}

// ========================================
// AUTOLOAD HELPER FUNCTIONS
// ========================================

use ISER\Core\Component\ComponentHelper;

/**
 * Load component's lib.php file if exists
 *
 * @deprecated Use ComponentHelper::getInstance()->requireLib() instead
 * @param string $component Component name (e.g., 'auth_manual', 'tool_uploaduser')
 * @return bool True if loaded, false otherwise
 */
function require_component_lib(string $component): bool
{
    return ComponentHelper::getInstance()->requireLib($component);
}

/**
 * Get component directory path
 *
 * @deprecated Use ComponentHelper::getInstance()->getPath() instead
 * @param string $component Component name (e.g., 'auth_manual', 'tool_uploaduser')
 * @return string|null Path to component directory or null if not found
 */
function component_get_path(string $component): ?string
{
    return ComponentHelper::getInstance()->getPath($component);
}

/**
 * Get list of all installed components of a specific type
 *
 * @deprecated Use ComponentHelper::getInstance()->getComponentsByType() instead
 * @param string $type Component type (e.g., 'auth', 'tool', 'theme')
 * @return array Array of component names
 */
function get_components_by_type(string $type): array
{
    return ComponentHelper::getInstance()->getComponentsByType($type);
}

// ========================================
// LOAD CORE HELPER FILES
// ========================================

// These will be created in subsequent phases
// For now, we'll create placeholders

$helperFiles = [
    // LIB_DIR . '/accesslib.php',     // RBAC functions (Phase 2)
    // LIB_DIR . '/outputlib.php',     // Output/rendering functions (Phase 7)
    // LIB_DIR . '/pluginlib.php',     // Plugin management (Phase 4)
];

foreach ($helperFiles as $helperFile) {
    if (file_exists($helperFile)) {
        require_once $helperFile;
    }
}

// ========================================
// INITIALIZATION COMPLETE
// ========================================

// Log setup completion (only if logger is available)
if (function_exists('error_log')) {
    error_log('[NexoSupport] System setup completed - Version ' . NEXOSUPPORT_VERSION);
}
