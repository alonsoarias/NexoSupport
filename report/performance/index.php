<?php
/**
 * Performance Report
 *
 * @package report_performance
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:viewreports');

global $USER, $CFG, $DB;

// Collect performance metrics
$metrics = [];

// PHP Info
$metrics[] = [
    'category' => 'PHP',
    'name' => 'PHP Version',
    'value' => PHP_VERSION,
    'status_ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'status_warning' => version_compare(PHP_VERSION, '7.4.0', '>=') && version_compare(PHP_VERSION, '8.0.0', '<'),
];

$metrics[] = [
    'category' => 'PHP',
    'name' => 'Memory Limit',
    'value' => ini_get('memory_limit'),
    'status_ok' => true,
];

$metrics[] = [
    'category' => 'PHP',
    'name' => 'Max Execution Time',
    'value' => ini_get('max_execution_time') . 's',
    'status_ok' => true,
];

$metrics[] = [
    'category' => 'PHP',
    'name' => 'OPcache',
    'value' => function_exists('opcache_get_status') && opcache_get_status() ? 'Enabled' : 'Disabled',
    'status_ok' => function_exists('opcache_get_status') && opcache_get_status(),
    'status_warning' => !function_exists('opcache_get_status') || !opcache_get_status(),
];

// Database
$dbsize = $DB->get_field_sql("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()");
$metrics[] = [
    'category' => 'Database',
    'name' => 'Database Size',
    'value' => $dbsize ? \core\cache\cache_manager::format_bytes($dbsize) : 'Unknown',
    'status_ok' => true,
];

$tablecount = $DB->count_records_sql("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()");
$metrics[] = [
    'category' => 'Database',
    'name' => 'Table Count',
    'value' => $tablecount,
    'status_ok' => true,
];

// Users
$usercount = $DB->count_records('users', ['deleted' => 0]);
$metrics[] = [
    'category' => 'System',
    'name' => 'Total Users',
    'value' => number_format($usercount),
    'status_ok' => true,
];

// Sessions
$sessioncount = $DB->count_records('sessions');
$metrics[] = [
    'category' => 'System',
    'name' => 'Active Sessions',
    'value' => number_format($sessioncount),
    'status_ok' => $sessioncount < 1000,
    'status_warning' => $sessioncount >= 1000,
];

// Disk space
$dataroot = $CFG->dataroot ?? BASE_DIR . '/var';
if (is_dir($dataroot)) {
    $freespace = disk_free_space($dataroot);
    $metrics[] = [
        'category' => 'System',
        'name' => 'Disk Free Space',
        'value' => $freespace ? \core\cache\cache_manager::format_bytes($freespace) : 'Unknown',
        'status_ok' => $freespace > 1073741824, // > 1GB
        'status_warning' => $freespace <= 1073741824 && $freespace > 104857600, // 100MB-1GB
        'status_critical' => $freespace <= 104857600, // < 100MB
    ];
}

$context = [
    'pagetitle' => get_string('performancereport', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'metrics' => $metrics,
    'hasmetrics' => !empty($metrics),
    'phpversion' => PHP_VERSION,
    'servertime' => date('Y-m-d H:i:s'),
    'sesskey' => sesskey(),
];

echo render_template('report/performance', $context);
