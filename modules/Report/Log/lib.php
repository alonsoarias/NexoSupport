<?php
/**
 * NexoSupport - Log Report Library
 *
 * @package    report_log
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get report capabilities
 *
 * @return array Capabilities
 */
function report_log_get_capabilities(): array
{
    return [
        'report/log:view' => [
            'name' => 'View logs report',
            'description' => 'View system logs and activity reports',
            'module' => 'report_log',
        ],
        'report/log:export' => [
            'name' => 'Export logs',
            'description' => 'Export log data to various formats (CSV, JSON, XML)',
            'module' => 'report_log',
        ],
        'report/log:security' => [
            'name' => 'View security report',
            'description' => 'View security-related logs and alerts',
            'module' => 'report_log',
        ],
    ];
}

/**
 * Get report title
 *
 * @return string Report title
 */
function report_log_get_title(): string
{
    return __('System Logs');
}

/**
 * Get report description
 *
 * @return string Report description
 */
function report_log_get_description(): string
{
    return __('Comprehensive system logging and activity reporting');
}

/**
 * Get menu items for this report
 *
 * @return array Menu items
 */
function report_log_get_menu_items(): array
{
    $items = [];

    if (has_capability('report/log:view')) {
        $items[] = [
            'title' => 'System Logs',
            'url' => '/modules/Report/Log',
            'icon' => 'file-text',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/modules/Report/Log') === 0,
        ];
    }

    if (has_capability('report/log:security')) {
        $items[] = [
            'title' => 'Security Reports',
            'url' => '/modules/Report/Log/security',
            'icon' => 'shield',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/modules/Report/Log/security') === 0,
        ];
    }

    return $items;
}

/**
 * Get available log severity levels
 *
 * @return array Severity levels
 */
function report_log_get_severity_levels(): array
{
    return [
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
        3 => 'Critical',
    ];
}

/**
 * Get available CRUD operations
 *
 * @return array CRUD operations
 */
function report_log_get_crud_operations(): array
{
    return [
        'c' => 'Create',
        'r' => 'Read',
        'u' => 'Update',
        'd' => 'Delete',
    ];
}

/**
 * Get available export formats
 *
 * @return array Export formats
 */
function report_log_get_export_formats(): array
{
    return [
        'csv' => [
            'name' => 'CSV',
            'description' => 'Comma-separated values',
            'mime' => 'text/csv',
            'extension' => 'csv',
        ],
        'json' => [
            'name' => 'JSON',
            'description' => 'JavaScript Object Notation',
            'mime' => 'application/json',
            'extension' => 'json',
        ],
        'xml' => [
            'name' => 'XML',
            'description' => 'Extensible Markup Language',
            'mime' => 'application/xml',
            'extension' => 'xml',
        ],
    ];
}

/**
 * Get report configuration options
 *
 * @return array Configuration options
 */
function report_log_get_config_options(): array
{
    return [
        'retention_days' => [
            'name' => 'Log retention period (days)',
            'description' => 'Number of days to keep logs before automatic deletion',
            'type' => 'int',
            'default' => 90,
            'min' => 7,
            'max' => 365,
        ],
        'max_export_rows' => [
            'name' => 'Maximum export rows',
            'description' => 'Maximum number of rows to export at once',
            'type' => 'int',
            'default' => 10000,
            'min' => 100,
            'max' => 100000,
        ],
        'enable_security_alerts' => [
            'name' => 'Enable security alerts',
            'description' => 'Send alerts for critical security events',
            'type' => 'bool',
            'default' => true,
        ],
        'alert_email' => [
            'name' => 'Security alert email',
            'description' => 'Email address to receive security alerts',
            'type' => 'email',
            'default' => '',
        ],
        'log_failed_logins' => [
            'name' => 'Log failed login attempts',
            'description' => 'Record all failed login attempts',
            'type' => 'bool',
            'default' => true,
        ],
        'log_permission_failures' => [
            'name' => 'Log permission failures',
            'description' => 'Record when users attempt unauthorized actions',
            'type' => 'bool',
            'default' => true,
        ],
    ];
}

/**
 * Get report features
 *
 * @return array Features
 */
function report_log_get_features(): array
{
    return [
        'comprehensive_logging' => 'Track all system activities',
        'security_monitoring' => 'Monitor security-related events',
        'export_capabilities' => 'Export logs in multiple formats',
        'retention_management' => 'Automatic log retention and cleanup',
        'alert_system' => 'Real-time security alerts',
        'crud_tracking' => 'Track all CRUD operations',
    ];
}

/**
 * Validate log filter parameters
 *
 * @param array $filters Filter parameters
 * @return array Validation errors (empty if valid)
 */
function report_log_validate_filters(array $filters): array
{
    $errors = [];

    if (isset($filters['severity']) && !in_array($filters['severity'], [0, 1, 2, 3])) {
        $errors['severity'] = 'Invalid severity level';
    }

    if (isset($filters['crud']) && !in_array($filters['crud'], ['c', 'r', 'u', 'd'])) {
        $errors['crud'] = 'Invalid CRUD operation';
    }

    if (isset($filters['date_from'])) {
        $timestamp = strtotime($filters['date_from']);
        if ($timestamp === false) {
            $errors['date_from'] = 'Invalid date format';
        }
    }

    if (isset($filters['date_to'])) {
        $timestamp = strtotime($filters['date_to']);
        if ($timestamp === false) {
            $errors['date_to'] = 'Invalid date format';
        }
    }

    if (isset($filters['date_from'], $filters['date_to'])) {
        if (strtotime($filters['date_from']) > strtotime($filters['date_to'])) {
            $errors['date_range'] = 'Start date must be before end date';
        }
    }

    return $errors;
}
