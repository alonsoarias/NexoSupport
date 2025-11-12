<?php

/**
 * Reports translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'title' => 'Reports',
    'generate' => 'Generate Report',
    'view' => 'View Report',
    'export' => 'Export Report',

    // Report types
    'types' => [
        'users' => 'User Report',
        'roles' => 'Role Report',
        'permissions' => 'Permission Report',
        'activity' => 'Activity Report',
        'logins' => 'Login Report',
        'audit' => 'Audit Report',
        'security' => 'Security Report',
        'performance' => 'Performance Report',
        'system' => 'System Report',
    ],

    // Periods
    'periods' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
        'custom' => 'Custom',
    ],

    // Export formats
    'formats' => [
        'pdf' => 'PDF',
        'excel' => 'Excel (XLSX)',
        'csv' => 'CSV',
        'json' => 'JSON',
        'html' => 'HTML',
    ],

    // Report configuration
    'config' => [
        'title' => 'Report Configuration',
        'type' => 'Report Type',
        'period' => 'Period',
        'date_from' => 'From',
        'date_to' => 'To',
        'format' => 'Format',
        'include_charts' => 'Include Charts',
        'include_summary' => 'Include Summary',
        'filters' => 'Filters',
    ],

    // Metrics
    'metrics' => [
        'total' => 'Total',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'new' => 'New',
        'deleted' => 'Deleted',
        'growth' => 'Growth',
        'percentage' => 'Percentage',
        'average' => 'Average',
    ],

    // User report
    'users' => [
        'total_users' => 'Total Users',
        'new_users' => 'New Users',
        'active_users' => 'Active Users',
        'suspended_users' => 'Suspended Users',
        'by_role' => 'Users by Role',
        'by_status' => 'Users by Status',
        'registration_trend' => 'Registration Trend',
    ],

    // Login report
    'logins' => [
        'total_logins' => 'Total Logins',
        'successful_logins' => 'Successful Logins',
        'failed_logins' => 'Failed Logins',
        'unique_users' => 'Unique Users',
        'by_hour' => 'Logins by Hour',
        'by_day' => 'Logins by Day',
        'by_location' => 'Logins by Location',
        'peak_times' => 'Peak Times',
    ],

    // Security report
    'security' => [
        'failed_attempts' => 'Failed Attempts',
        'locked_accounts' => 'Locked Accounts',
        'suspicious_activity' => 'Suspicious Activity',
        'ip_blocks' => 'Blocked IPs',
        'password_resets' => 'Password Resets',
        'mfa_usage' => '2FA Usage',
    ],

    // Activity report
    'activity' => [
        'user_actions' => 'User Actions',
        'most_active_users' => 'Most Active Users',
        'action_types' => 'Action Types',
        'activity_timeline' => 'Activity Timeline',
    ],

    // Messages
    'generating' => 'Generating report...',
    'generated_successfully' => 'Report generated successfully',
    'generation_failed' => 'Error generating report',
    'no_data' => 'No data available for the selected period',
    'exported_successfully' => 'Report exported successfully',

    // Actions
    'generate_button' => 'Generate',
    'export_button' => 'Export',
    'print_button' => 'Print',
    'share_button' => 'Share',
    'schedule_button' => 'Schedule',
    'download_button' => 'Download',

    // Scheduled reports
    'scheduled' => [
        'title' => 'Scheduled Reports',
        'create' => 'Schedule Report',
        'frequency' => 'Frequency',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'recipients' => 'Recipients',
        'next_run' => 'Next Run',
        'last_run' => 'Last Run',
    ],
];
