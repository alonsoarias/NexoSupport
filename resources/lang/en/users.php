<?php

/**
 * User management translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'management_title' => 'User Management',
    'list_title' => 'Users List',
    'create_title' => 'Create User',
    'edit_title' => 'Edit User',
    'view_title' => 'View User',
    'profile_title' => 'User Profile',

    // Fields
    'id' => 'ID',
    'username' => 'Username',
    'email' => 'Email',
    'password' => 'Password',
    'password_confirm' => 'Confirm Password',
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'full_name' => 'Full Name',
    'status' => 'Status',
    'role' => 'Role',
    'roles' => 'Roles',
    'created_at' => 'Registration Date',
    'updated_at' => 'Last Updated',
    'last_login' => 'Last Login',
    'last_login_ip' => 'Last Login IP',
    'avatar' => 'Avatar',
    'bio' => 'Biography',
    'phone' => 'Phone',
    'timezone' => 'Timezone',
    'locale' => 'Language',

    // Actions
    'create_button' => 'Create User',
    'edit_button' => 'Edit',
    'delete_button' => 'Delete',
    'restore_button' => 'Restore',
    'suspend_button' => 'Suspend',
    'activate_button' => 'Activate',
    'reset_password' => 'Reset Password',
    'send_verification' => 'Send Verification',
    'view_profile' => 'View Profile',
    'edit_profile' => 'Edit Profile',

    // Messages
    'created_message' => 'User :name created successfully',
    'updated_message' => 'User :name updated successfully',
    'deleted_message' => 'User :name deleted successfully',
    'restored_message' => 'User :name restored successfully',
    'suspended_message' => 'User :name suspended',
    'activated_message' => 'User :name activated',
    'password_reset_sent' => 'Reset link sent to :email',
    'verification_sent' => 'Verification email sent to :email',

    // Status
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'status_suspended' => 'Suspended',
    'status_deleted' => 'Deleted',
    'status_pending' => 'Pending',

    // Filters
    'filter_all' => 'All',
    'filter_active' => 'Active',
    'filter_inactive' => 'Inactive',
    'filter_suspended' => 'Suspended',
    'filter_deleted' => 'Deleted',
    'filter_by_role' => 'Filter by Role',
    'search_placeholder' => 'Search users...',

    // Placeholders
    'username_placeholder' => 'Enter username',
    'email_placeholder' => 'user@example.com',
    'password_placeholder' => 'Enter password',
    'first_name_placeholder' => 'Enter first name',
    'last_name_placeholder' => 'Enter last name',
    'phone_placeholder' => '+XX XXXXXXXXX',

    // Confirmations
    'delete_confirm' => 'Are you sure you want to delete user :name?',
    'suspend_confirm' => 'Are you sure you want to suspend user :name?',
    'restore_confirm' => 'Are you sure you want to restore user :name?',

    // Validations
    'username_required' => 'Username is required',
    'username_min_length' => 'Username must be at least :min characters',
    'username_format' => 'Username can only contain letters, numbers, and underscores',
    'username_unique' => 'Username is already taken',
    'email_required' => 'Email is required',
    'email_valid' => 'Enter a valid email address',
    'email_unique' => 'Email is already registered',
    'password_required' => 'Password is required',
    'password_min' => 'Password must be at least :min characters',
    'password_confirm_match' => 'Passwords do not match',

    // Statistics
    'total_users' => 'Total Users',
    'active_users' => 'Active Users',
    'new_today' => 'New Today',
    'online_now' => 'Online Now',

    // Counter with pluralization
    'count_label' => '{0} No users|{1} 1 user|[2,*] :count users',
];
