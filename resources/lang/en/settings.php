<?php

/**
 * System settings translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Title
    'title' => 'System Settings',

    // Settings groups
    'groups' => [
        'general' => 'General',
        'email' => 'Email',
        'security' => 'Security',
        'cache' => 'Cache',
        'logs' => 'Logs',
        'regional' => 'Regional',
        'appearance' => 'Appearance',
        'advanced' => 'Advanced',
    ],

    // General settings
    'general' => [
        'app_name' => 'Application Name',
        'app_url' => 'Application URL',
        'app_env' => 'Environment',
        'app_debug' => 'Debug Mode',
        'maintenance_mode' => 'Maintenance Mode',
        'timezone' => 'Timezone',
        'locale' => 'Language',
    ],

    // Email settings
    'email' => [
        'driver' => 'Email Driver',
        'host' => 'SMTP Server',
        'port' => 'Port',
        'username' => 'Username',
        'password' => 'Password',
        'encryption' => 'Encryption',
        'from_address' => 'From Address',
        'from_name' => 'From Name',
        'test_connection' => 'Test Connection',
    ],

    // Security settings
    'security' => [
        'password_min_length' => 'Minimum Password Length',
        'password_require_uppercase' => 'Require Uppercase',
        'password_require_lowercase' => 'Require Lowercase',
        'password_require_numbers' => 'Require Numbers',
        'password_require_symbols' => 'Require Symbols',
        'password_expiry_days' => 'Password Expiry Days',
        'max_login_attempts' => 'Maximum Login Attempts',
        'lockout_duration' => 'Lockout Duration (minutes)',
        'session_lifetime' => 'Session Duration (minutes)',
        'jwt_secret' => 'JWT Secret Key',
        'jwt_ttl' => 'JWT Token TTL (minutes)',
        'mfa_enabled' => 'Enable Two-Factor Authentication',
    ],

    // Cache settings
    'cache' => [
        'driver' => 'Cache Driver',
        'ttl' => 'Time to Live (seconds)',
        'prefix' => 'Key Prefix',
        'clear_cache' => 'Clear Cache',
    ],

    // Log settings
    'logs' => [
        'channel' => 'Log Channel',
        'level' => 'Log Level',
        'max_files' => 'Maximum Files',
        'rotation' => 'File Rotation',
    ],

    // Regional settings
    'regional' => [
        'default_timezone' => 'Default Timezone',
        'default_locale' => 'Default Language',
        'available_locales' => 'Available Languages',
        'date_format' => 'Date Format',
        'time_format' => 'Time Format',
        'currency' => 'Currency',
    ],

    // Messages
    'saved_message' => 'Settings saved successfully',
    'restored_message' => 'Settings restored to default values',
    'test_email_sent' => 'Test email sent to :email',
    'cache_cleared' => 'Cache cleared successfully',

    // Actions
    'save' => 'Save Settings',
    'restore_defaults' => 'Restore Default Values',
    'cancel' => 'Cancel',

    // Help
    'help' => [
        'app_name' => 'Name that will appear throughout the system',
        'app_url' => 'Base URL of the application (without trailing slash)',
        'app_debug' => 'Show detailed errors (development only)',
        'password_min_length' => 'Minimum number of characters required for passwords',
        'max_login_attempts' => 'Number of failed attempts before locking account',
        'session_lifetime' => 'Time of inactivity before logging out',
    ],
];
