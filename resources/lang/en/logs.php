<?php

/**
 * System logs translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'title' => 'System Logs',
    'view' => 'View Logs',
    'search' => 'Search Logs',

    // Log levels
    'levels' => [
        'emergency' => 'Emergency',
        'alert' => 'Alert',
        'critical' => 'Critical',
        'error' => 'Error',
        'warning' => 'Warning',
        'notice' => 'Notice',
        'info' => 'Information',
        'debug' => 'Debug',
    ],

    // Channels
    'channels' => [
        'application' => 'Application',
        'security' => 'Security',
        'database' => 'Database',
        'authentication' => 'Authentication',
        'authorization' => 'Authorization',
        'api' => 'API',
        'email' => 'Email',
        'cache' => 'Cache',
        'queue' => 'Queue',
    ],

    // Filters
    'filters' => [
        'level' => 'Level',
        'channel' => 'Channel',
        'date_from' => 'From',
        'date_to' => 'To',
        'user' => 'User',
        'ip' => 'IP Address',
        'message' => 'Message',
    ],

    // Fields
    'timestamp' => 'Date and Time',
    'level' => 'Level',
    'channel' => 'Channel',
    'message' => 'Message',
    'context' => 'Context',
    'user' => 'User',
    'ip' => 'IP',
    'user_agent' => 'Browser',
    'url' => 'URL',
    'method' => 'Method',
    'stack_trace' => 'Stack Trace',

    // Event types
    'events' => [
        'user_login' => 'User login',
        'user_logout' => 'User logout',
        'login_failed' => 'Failed login attempt',
        'user_created' => 'User created',
        'user_updated' => 'User updated',
        'user_deleted' => 'User deleted',
        'password_changed' => 'Password changed',
        'password_reset' => 'Password reset',
        'role_assigned' => 'Role assigned',
        'permission_changed' => 'Permission changed',
        'settings_updated' => 'Settings updated',
        'file_uploaded' => 'File uploaded',
        'database_query' => 'Database query',
        'api_request' => 'API request',
        'error_occurred' => 'Error occurred',
        'exception_thrown' => 'Exception thrown',
    ],

    // Actions
    'view_details' => 'View Details',
    'export' => 'Export',
    'clear_logs' => 'Clear Logs',
    'download' => 'Download',
    'refresh' => 'Refresh',

    // Messages
    'no_logs' => 'No logs available for the selected period',
    'loading' => 'Loading logs...',
    'exported_successfully' => 'Logs exported successfully',
    'cleared_successfully' => 'Logs cleared successfully',
    'clear_confirm' => 'Are you sure you want to clear the logs?',
    'clear_warning' => 'This action cannot be undone',

    // Statistics
    'stats' => [
        'total_entries' => 'Total Entries',
        'errors_today' => 'Errors Today',
        'warnings_today' => 'Warnings Today',
        'by_level' => 'By Level',
        'by_channel' => 'By Channel',
        'most_common' => 'Most Common',
    ],

    // Log configuration
    'configuration' => [
        'title' => 'Log Configuration',
        'log_level' => 'Log Level',
        'log_channel' => 'Log Channel',
        'max_files' => 'Maximum Files',
        'max_file_size' => 'Maximum File Size',
        'rotation' => 'File Rotation',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ],

    // Table
    'table' => [
        'showing' => 'Showing :from to :to of :total logs',
        'per_page' => 'Per page',
        'no_results' => 'No results found',
    ],
];
