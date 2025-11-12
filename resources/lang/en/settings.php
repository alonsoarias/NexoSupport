<?php

/**
 * System settings translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Title
    'title' => 'System Settings',
    'system_title' => 'System Settings',
    'description' => 'Configure all aspects of the system from this centralized interface',

    // Tabs
    'tabs' => [
        'general' => 'General',
        'email' => 'Email',
        'security' => 'Security',
        'appearance' => 'Appearance',
        'advanced' => 'Advanced',
    ],

    // Settings groups
    'groups' => [
        'general' => 'General Settings',
        'email' => 'Email Settings',
        'security' => 'Security Settings',
        'appearance' => 'Appearance Settings',
        'advanced' => 'Advanced Settings',
    ],

    // Fields
    'fields' => [
        // General
        'site_name' => 'Site Name',
        'site_description' => 'Site Description',
        'timezone' => 'Timezone',
        'locale' => 'Language',
        'date_format' => 'Date Format',

        // Email
        'from_name' => 'From Name',
        'from_address' => 'From Address',
        'reply_to' => 'Reply To',
        'mail_driver' => 'Mail Driver',

        // Security
        'session_lifetime' => 'Session Lifetime (minutes)',
        'password_min_length' => 'Minimum Password Length',
        'require_email_verification' => 'Require Email Verification',
        'login_max_attempts' => 'Maximum Login Attempts',
        'lockout_duration' => 'Lockout Duration (minutes)',

        // Appearance
        'theme' => 'Theme',
        'items_per_page' => 'Items Per Page',
        'default_language' => 'Default Language',

        // Advanced
        'cache_driver' => 'Cache Driver',
        'log_level' => 'Log Level',
        'debug_mode' => 'Debug Mode',
        'maintenance_mode' => 'Maintenance Mode',
    ],

    // Help texts
    'help' => [
        // General
        'site_name' => 'Name that will appear throughout the system',
        'site_description' => 'Brief description of the system purpose',
        'timezone' => 'Timezone for system dates and times',
        'locale' => 'Default interface language',
        'date_format' => 'Date display format',

        // Email
        'from_name' => 'Name that will appear as email sender',
        'from_address' => 'Email address for outgoing messages',
        'reply_to' => 'Address for user replies',
        'mail_driver' => 'Email sending method (SMTP recommended)',

        // Security
        'session_lifetime' => 'Time of inactivity before automatic logout (5-1440 minutes)',
        'password_min_length' => 'Minimum number of characters for passwords (6-32)',
        'require_email_verification' => 'Users must verify their email before accessing',
        'login_max_attempts' => 'Failed attempts allowed before locking account (3-20)',
        'lockout_duration' => 'Lock time after exceeding attempts (1-1440 minutes)',

        // Appearance
        'theme' => 'System visual theme',
        'items_per_page' => 'Number of items in lists and tables (10-100)',
        'default_language' => 'Default language for new users',

        // Advanced
        'cache_driver' => 'Cache storage system',
        'log_level' => 'Level of detail in system logs',
        'debug_mode' => 'Show detailed errors - DEVELOPMENT ONLY',
        'maintenance_mode' => 'Disable site for everyone except administrators',
    ],

    // Messages
    'saved_message' => 'Settings saved successfully',
    'restored_message' => 'Settings restored to default values',
    'items_updated' => 'items updated',

    // Actions
    'actions' => [
        'save' => 'Save Changes',
        'cancel' => 'Cancel',
        'reset' => 'Restore Default Values',
    ],

    // Warnings
    'warnings' => [
        'advanced' => 'WARNING: Advanced settings can affect system operation. Modify with caution.',
    ],

    // Badges
    'badges' => [
        'sensitive' => 'Sensitive',
        'critical' => 'Critical',
    ],

    // Confirmations
    'confirmations' => [
        'reset' => 'Are you sure you want to restore all settings to their default values? This action cannot be undone.',
        'sensitive' => 'WARNING: You have enabled sensitive settings that may affect system operation:',
    ],
];
