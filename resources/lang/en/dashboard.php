<?php

/**
 * Dashboard translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Title
    'title' => 'Dashboard',
    'welcome' => 'Welcome, :name',
    'welcome_message' => 'Welcome to the ISER authentication system',

    // Statistics widgets
    'stats' => [
        'total_users' => 'Total Users',
        'active_roles' => 'Active Roles',
        'plugins_installed' => 'Plugins Installed',
        'logins_today' => 'Logins Today',
        'new_users_week' => 'New Users (7 days)',
        'failed_logins_today' => 'Failed Attempts Today',
        'active_sessions' => 'Active Sessions',
        'system_health' => 'System Health',
    ],

    // Charts
    'charts' => [
        'user_activity' => 'User Activity',
        'login_attempts' => 'Login Attempts',
        'user_growth' => 'User Growth',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'success' => 'Successful',
        'failed' => 'Failed',
    ],

    // Quick actions
    'quick_actions' => [
        'title' => 'Quick Actions',
        'create_user' => 'Create User',
        'create_role' => 'Create Role',
        'view_logs' => 'View Logs',
        'view_audit' => 'View Audit',
        'system_reports' => 'System Reports',
        'clear_cache' => 'Clear Cache',
    ],

    // Recent activity
    'recent_activity' => [
        'title' => 'Recent Activity',
        'no_activity' => 'No recent activity',
        'user_created' => ':user created user :target',
        'user_updated' => ':user updated user :target',
        'user_deleted' => ':user deleted user :target',
        'role_created' => ':user created role :target',
        'role_updated' => ':user updated role :target',
        'login_success' => ':user logged in',
        'login_failed' => 'Failed login attempt from :ip',
        'settings_updated' => ':user updated settings',
    ],

    // System information
    'system_info' => [
        'title' => 'System Information',
        'version' => 'Version',
        'php_version' => 'PHP Version',
        'database' => 'Database',
        'server_time' => 'Server Time',
        'uptime' => 'Uptime',
        'disk_space' => 'Disk Space',
        'memory_usage' => 'Memory Usage',
    ],

    // Alerts
    'alerts' => [
        'title' => 'System Alerts',
        'no_alerts' => 'No alerts',
        'update_available' => 'Update available',
        'disk_space_low' => 'Low disk space',
        'failed_logins_high' => 'High number of failed login attempts',
        'certificate_expiring' => 'SSL certificate expiring soon',
    ],

    // Recent users
    'recent_users' => [
        'title' => 'Recent Users',
        'view_all' => 'View All',
        'online' => 'Online',
        'offline' => 'Offline',
    ],

    // Calendar
    'calendar' => [
        'title' => 'Calendar',
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'no_events' => 'No scheduled events',
    ],
];
