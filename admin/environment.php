<?php
/**
 * Environment Information Page
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();
require_capability('nexosupport/admin:managesettings');

global $CFG, $DB;

// Get environment information
$environment = [
    'php_version' => PHP_VERSION,
    'php_sapi' => php_sapi_name(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'os' => PHP_OS,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
];

// Get database info
$db_info = [
    'type' => $CFG->dbtype ?? 'mysql',
    'host' => $CFG->dbhost ?? 'localhost',
    'name' => $CFG->dbname ?? 'N/A',
];

// Check required extensions
$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'json' => extension_loaded('json'),
    'openssl' => extension_loaded('openssl'),
    'curl' => extension_loaded('curl'),
    'gd' => extension_loaded('gd'),
    'zip' => extension_loaded('zip'),
];

$context = [
    'pagetitle' => get_string('environment', 'admin'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'environment' => $environment,
    'db_info' => $db_info,
    'extensions' => $extensions,
    'version' => $CFG->version ?? '1.0.0',
];

echo render_template('admin/environment', $context);
