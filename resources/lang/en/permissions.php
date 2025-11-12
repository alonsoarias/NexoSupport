<?php

/**
 * Permission management translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'management_title' => 'Permission Management',
    'list_title' => 'Permissions List',
    'by_module' => 'Permissions by Module',
    'role_permissions' => 'Role Permissions',

    // Modules
    'modules' => [
        'users' => 'Users',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'logs' => 'Logs',
        'audit' => 'Audit',
        'reports' => 'Reports',
        'sessions' => 'Sessions',
        'plugins' => 'Plugins',
    ],

    // Permission actions
    'actions' => [
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'export' => 'Export',
        'import' => 'Import',
        'manage' => 'Manage',
        'assign' => 'Assign',
    ],

    // Permission levels
    'levels' => [
        'inherit' => 'Inherit',
        'allow' => 'Allow',
        'prevent' => 'Prevent',
        'prohibit' => 'Prohibit',
    ],

    // Level descriptions
    'level_descriptions' => [
        'inherit' => 'Inherit permissions from parent role or default settings',
        'allow' => 'Explicitly allow this action',
        'prevent' => 'Prevent this action, but it can be overridden by another role',
        'prohibit' => 'Absolutely prohibit this action, cannot be overridden',
    ],

    // Permission descriptions by module
    'descriptions' => [
        'users' => [
            'view' => 'View list and details of users',
            'create' => 'Create new users',
            'update' => 'Update information of existing users',
            'delete' => 'Delete users (soft delete)',
            'restore' => 'Restore deleted users',
            'export' => 'Export user data',
        ],
        'roles' => [
            'view' => 'View list and details of roles',
            'create' => 'Create new roles',
            'update' => 'Update existing roles',
            'delete' => 'Delete custom roles',
            'assign' => 'Assign roles to users',
        ],
        'permissions' => [
            'view' => 'View system permissions',
            'manage' => 'Manage role permissions',
        ],
        'settings' => [
            'view' => 'View system settings',
            'update' => 'Update system settings',
        ],
        'logs' => [
            'view' => 'View system logs',
            'export' => 'Export logs',
        ],
        'audit' => [
            'view' => 'View audit logs',
            'export' => 'Export audit',
        ],
        'reports' => [
            'view' => 'View reports',
            'create' => 'Generate new reports',
            'export' => 'Export reports',
        ],
    ],

    // Messages
    'created_message' => 'Permission :name created successfully',
    'updated_message' => 'Permission :name updated successfully',
    'deleted_message' => 'Permission :name deleted successfully',
    'no_permissions' => 'You do not have permission to perform this action',
    'permission_denied' => 'Access denied',
    'name_required' => 'Permission name is required',

    // Search and filters
    'search_placeholder' => 'Search permissions...',
    'filter_by_module' => 'Filter by Module',
    'filter_by_level' => 'Filter by Level',
];
