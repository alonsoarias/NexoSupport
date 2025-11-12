<?php

/**
 * Role management translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'management_title' => 'Role Management',
    'list_title' => 'Roles List',
    'create_title' => 'Create Role',
    'edit_title' => 'Edit Role',
    'view_title' => 'View Role',
    'permissions_title' => 'Role Permissions',

    // Fields
    'id' => 'ID',
    'name' => 'Name',
    'shortname' => 'Short Name',
    'description' => 'Description',
    'permissions' => 'Permissions',
    'users_count' => 'Assigned Users',
    'is_system' => 'System Role',
    'created_at' => 'Created Date',
    'updated_at' => 'Last Updated',

    // Actions
    'create_button' => 'Create Role',
    'edit_button' => 'Edit',
    'delete_button' => 'Delete',
    'clone_button' => 'Clone',
    'assign_permissions' => 'Assign Permissions',
    'view_users' => 'View Users',
    'assign_to_user' => 'Assign to User',

    // Messages
    'created_message' => 'Role :name created successfully',
    'updated_message' => 'Role :name updated successfully',
    'deleted_message' => 'Role :name deleted successfully',
    'cloned_message' => 'Role :name cloned as :new_name',
    'permissions_updated' => 'Permissions for role :name updated',
    'system_role_warning' => 'This is a system role and cannot be deleted',
    'system_role_error' => 'System roles cannot be deleted',
    'users_assigned_warning' => 'This role has :count users assigned',

    // Placeholders
    'name_placeholder' => 'E.g: Administrator',
    'shortname_placeholder' => 'E.g: admin',
    'description_placeholder' => 'Role description...',
    'search_placeholder' => 'Search roles...',

    // Confirmations
    'delete_confirm' => 'Are you sure you want to delete the role :name?',
    'delete_with_users_confirm' => 'This role has :count users assigned. Are you sure you want to delete it?',

    // Validations
    'name_required' => 'Role name is required',
    'name_unique' => 'A role with this name already exists',
    'shortname_required' => 'Short name is required',
    'shortname_unique' => 'A role with this short name already exists',
    'shortname_format' => 'Short name can only contain lowercase letters, numbers, and hyphens',

    // Predefined roles
    'roles' => [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'user' => 'User',
        'guest' => 'Guest',
    ],

    // Statistics
    'total_roles' => 'Total Roles',
    'system_roles' => 'System Roles',
    'custom_roles' => 'Custom Roles',

    // Counters
    'count_label' => '{0} No roles|{1} 1 role|[2,*] :count roles',
    'users_count_label' => '{0} No users|{1} 1 user|[2,*] :count users',
];
