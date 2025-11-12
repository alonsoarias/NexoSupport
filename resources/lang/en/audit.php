<?php

/**
 * Audit trail translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'title' => 'Audit Trail',
    'view' => 'View Audit',
    'search' => 'Search Audit',
    'trail' => 'Audit Trail',

    // Event types
    'event_types' => [
        'create' => 'Create',
        'read' => 'Read',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'login' => 'Login',
        'logout' => 'Logout',
        'failed_login' => 'Failed Login',
        'password_change' => 'Password Change',
        'password_reset' => 'Password Reset',
        'permission_change' => 'Permission Change',
        'role_change' => 'Role Change',
        'settings_change' => 'Settings Change',
        'export' => 'Export',
        'import' => 'Import',
    ],

    // Audited entities
    'entities' => [
        'user' => 'User',
        'role' => 'Role',
        'permission' => 'Permission',
        'setting' => 'Setting',
        'log' => 'Log',
        'session' => 'Session',
        'plugin' => 'Plugin',
        'report' => 'Report',
    ],

    // Fields
    'id' => 'ID',
    'timestamp' => 'Date and Time',
    'user' => 'User',
    'event' => 'Event',
    'entity_type' => 'Entity Type',
    'entity_id' => 'Entity ID',
    'description' => 'Description',
    'ip_address' => 'IP Address',
    'user_agent' => 'Browser',
    'old_values' => 'Old Values',
    'new_values' => 'New Values',
    'changes' => 'Changes',
    'details' => 'Details',

    // Filters
    'filters' => [
        'event_type' => 'Event Type',
        'entity_type' => 'Entity Type',
        'user' => 'User',
        'date_from' => 'From',
        'date_to' => 'To',
        'ip' => 'IP',
    ],

    // Event descriptions
    'descriptions' => [
        'user_created' => ':user created user :target',
        'user_updated' => ':user updated user :target',
        'user_deleted' => ':user deleted user :target',
        'user_restored' => ':user restored user :target',
        'role_created' => ':user created role :target',
        'role_updated' => ':user updated role :target',
        'role_deleted' => ':user deleted role :target',
        'role_assigned' => ':user assigned role :role to user :target',
        'role_removed' => ':user removed role :role from user :target',
        'permission_granted' => ':user granted permission :permission',
        'permission_revoked' => ':user revoked permission :permission',
        'settings_updated' => ':user updated setting :setting',
        'login_success' => ':user logged in from :ip',
        'login_failed' => 'Failed login attempt for :username from :ip',
        'logout' => ':user logged out',
        'password_changed' => ':user changed password',
        'password_reset' => ':user reset password for :target',
        'data_exported' => ':user exported :entity',
        'data_imported' => ':user imported :entity',
    ],

    // Actions
    'view_details' => 'View Details',
    'view_changes' => 'View Changes',
    'export' => 'Export Audit',
    'filter' => 'Filter',
    'clear_filters' => 'Clear Filters',
    'refresh' => 'Refresh',

    // Messages
    'no_records' => 'No audit records to display',
    'loading' => 'Loading audit...',
    'exported_successfully' => 'Audit exported successfully',

    // Statistics
    'stats' => [
        'total_events' => 'Total Events',
        'events_today' => 'Events Today',
        'unique_users' => 'Unique Users',
        'by_event_type' => 'By Event Type',
        'by_entity' => 'By Entity',
        'most_active_users' => 'Most Active Users',
        'recent_activity' => 'Recent Activity',
    ],

    // Change details
    'change_details' => [
        'field' => 'Field',
        'old_value' => 'Old Value',
        'new_value' => 'New Value',
        'no_changes' => 'No changes recorded',
    ],

    // Table
    'table' => [
        'showing' => 'Showing :from to :to of :total records',
        'per_page' => 'Per page',
        'no_results' => 'No results found',
    ],

    // Periods
    'periods' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom' => 'Custom',
    ],
];
