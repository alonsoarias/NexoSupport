<?php
/**
 * Language strings - Administration - English
 *
 * @package core
 * @subpackage admin
 * @copyright NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Administration index
$string['administration'] = 'Administration';
$string['admin_welcome'] = 'Welcome to the administration panel';
$string['admin_description'] = 'Manage all aspects of the system from here';
$string['quick_links'] = 'Quick Links';
$string['user_management'] = 'User Management';
$string['role_management'] = 'Role Management';
$string['system_settings'] = 'System Settings';
$string['cache_management'] = 'Cache Management';
$string['system_upgrade'] = 'System Upgrade';

// Upgrade
$string['upgrade'] = 'Upgrade';
$string['upgrade_title'] = 'NexoSupport System Upgrade';
$string['upgrade_description'] = 'Upgrade the system to the latest version';
$string['current_version'] = 'Current Version';
$string['target_version'] = 'Target Version';
$string['database_version'] = 'Database Version';
$string['code_version'] = 'Code Version';
$string['upgrade_required'] = 'Upgrade required';
$string['upgrade_not_required'] = 'System is up to date';
$string['upgrade_inprogress'] = 'Upgrade in progress';
$string['upgrade_success'] = 'Upgrade completed successfully';
$string['upgrade_error'] = 'Error during upgrade';
$string['upgrade_button'] = 'Run Upgrade';
$string['upgrade_requirements_title'] = 'Upgrade Requirements';
$string['upgrade_requirements_ok'] = 'All requirements are met';
$string['upgrade_requirements_failed'] = 'Some requirements are not met';
$string['upgrade_log'] = 'Upgrade Log';
$string['upgrade_warning'] = '⚠️ IMPORTANT: Backup the database before proceeding';
$string['upgrade_info'] = 'Upgrade Information';

// Cache management
$string['cache'] = 'Cache';
$string['cache_purge'] = 'Purge Cache';
$string['cache_purge_title'] = 'Purge System Cache';
$string['cache_purge_description'] = 'Remove all cache files to force regeneration';
$string['cache_purge_success'] = 'Cache purged successfully';
$string['cache_purge_error'] = 'Error purging cache';
$string['cache_purge_button'] = 'Purge Cache Now';
$string['cache_info'] = 'Cache improves performance by storing processed data. Purge it if you experience issues.';
$string['cache_confirm'] = 'Are you sure you want to purge the cache?';
$string['cache_status'] = 'Status';
$string['cache_enabled'] = 'Enabled';
$string['cache_disabled'] = 'Disabled';
$string['cache_memory_used'] = 'Memory Used';
$string['cache_memory_free'] = 'Memory Free';
$string['cache_memory_wasted'] = 'Memory Wasted';
$string['cache_scripts'] = 'Cached Scripts';
$string['cache_hits'] = 'Hits';
$string['cache_misses'] = 'Misses';
$string['cache_hit_rate'] = 'Hit Rate';
$string['cache_templates'] = 'Cached Templates';
$string['cache_size'] = 'Cache Size';
$string['cache_opcache_disabled'] = 'OPcache is disabled';
$string['cache_mustache_disabled'] = 'Mustache cache disabled';
$string['cache_purge_help'] = 'Select which caches to purge';
$string['cache_purge_all'] = 'Purge All Caches';
$string['cache_purge_opcache'] = 'Purge OPcache';
$string['cache_purge_mustache'] = 'Purge Mustache Cache';
$string['cache_purge_i18n'] = 'Purge i18n Cache';
$string['cache_purge_app'] = 'Purge Application Cache';
$string['cache_purge_rbac'] = 'Purge RBAC Cache';
$string['cache_about'] = 'About Caches';
$string['cache_opcache_help'] = 'PHP opcode cache - speeds up PHP execution';
$string['cache_mustache_help'] = 'Compiled Mustache templates cache';
$string['cache_i18n_help'] = 'Language strings cache';
$string['cache_app_help'] = 'Application-level cache for various data';
$string['cache_rbac_help'] = 'Role and permission definitions cache';
$string['cache_purge_results'] = 'Purge Results';

// Settings
$string['settings'] = 'Settings';
$string['settings_saved'] = 'Settings saved successfully';
$string['settings_error'] = 'Error saving settings';
$string['debugging'] = 'Debugging';
$string['debugging_title'] = 'Debugging Configuration';
$string['debugging_description'] = 'Configure debugging level and error display';
$string['debug_level'] = 'Debug Level';
$string['debug_none'] = 'None';
$string['debug_minimal'] = 'Minimal';
$string['debug_normal'] = 'Normal';
$string['debug_all'] = 'All (including developer)';
$string['debug_developer'] = 'Developer (maximum detail)';
$string['display_debug_info'] = 'Display debug information';
$string['debug_warning'] = '⚠️ Disable debugging in production for security and performance';

// User management
$string['users'] = 'Users';
$string['user_list'] = 'User List';
$string['user_create'] = 'Create User';
$string['user_edit'] = 'Edit User';
$string['user_delete'] = 'Delete User';
$string['user_view'] = 'View User';
$string['user_created'] = 'User created successfully';
$string['user_updated'] = 'User updated successfully';
$string['user_deleted'] = 'User deleted successfully';
$string['user_error'] = 'Error processing user';
$string['user_notfound'] = 'User not found';
$string['user_confirm_delete'] = 'Are you sure you want to delete this user?';
$string['user_details'] = 'User Details';
$string['user_info'] = 'User Information';
$string['user_roles'] = 'User Roles';
$string['user_status'] = 'Status';
$string['user_active'] = 'Active';
$string['user_suspended'] = 'Suspended';
$string['user_deleted_flag'] = 'Deleted';

// Role management
$string['roles'] = 'Roles';
$string['role_list'] = 'Role List';
$string['role_create'] = 'Create Role';
$string['role_edit'] = 'Edit Role';
$string['role_define'] = 'Define Role Permissions';
$string['role_assign'] = 'Assign Roles';
$string['role_delete'] = 'Delete Role';
$string['role_created'] = 'Role created successfully';
$string['role_updated'] = 'Role updated successfully';
$string['role_deleted'] = 'Role deleted successfully';
$string['role_error'] = 'Error processing role';
$string['role_notfound'] = 'Role not found';
$string['role_name'] = 'Role Name';
$string['role_shortname'] = 'Short Name';
$string['role_description'] = 'Description';
$string['role_permissions'] = 'Permissions';
$string['role_capabilities'] = 'Capabilities';
$string['role_archetype'] = 'Archetype';

// Permissions and capabilities
$string['allow'] = 'Allow';
$string['prevent'] = 'Prevent';
$string['prohibit'] = 'Prohibit';
$string['inherit'] = 'Inherit';
$string['notset'] = 'Not set';
$string['permission_updated'] = 'Permission updated';

// Common actions
$string['actions'] = 'Actions';
$string['confirm'] = 'Confirm';
$string['continue'] = 'Continue';
$string['back_to_admin'] = 'Back to Administration';
$string['no_data'] = 'No data available';
$string['loading'] = 'Loading...';
$string['processing'] = 'Processing...';

// Dashboard quick links
$string['plugins'] = 'Plugins';
$string['plugins_description'] = 'Manage plugins (Phase 2)';
$string['themes'] = 'Themes';
$string['themes_description'] = 'Customize appearance (Phase 6)';
$string['reports'] = 'Reports';
$string['reports_description'] = 'View system reports (Phase 5)';
$string['manage_users_description'] = 'Create, edit, and manage user accounts';
$string['manage_roles_description'] = 'Define roles and assign permissions';
$string['manage_settings_description'] = 'Configure system settings';

// Errors and messages
$string['error_occurred'] = 'An error occurred';
$string['operation_success'] = 'Operation completed successfully';
$string['operation_failed'] = 'Operation failed';
$string['invalid_request'] = 'Invalid request';
$string['missing_parameter'] = 'Missing parameter';

// Dashboard statistics
$string['total_users'] = 'Total Users';
$string['total_roles'] = 'Total Roles';
$string['system_version'] = 'System Version';
$string['cache_description'] = 'Manage and purge system cache';
$string["continue"] = "Continue";

// Plugins
$string['localplugins'] = 'Local plugins';
$string['blocks'] = 'Blocks';

// Plugin management
$string['installedplugins'] = 'Installed plugins';
$string['installplugin'] = 'Install plugin';
$string['plugintype'] = 'Plugin type';
$string['pluginzipfile'] = 'Plugin ZIP file';
$string['plugininstallhelp'] = 'Upload a plugin ZIP file. The plugin type will be auto-detected from the version.php file.';
$string['nopluginsinstalled'] = 'No plugins installed';
$string['nopluginsinstalledhelp'] = 'Install plugins to extend system functionality';
$string['pluginuninstalled'] = 'Plugin "{$a}" uninstalled successfully';
$string['pluginuninstallfailed'] = 'Error uninstalling plugin';
$string['plugincannotuninstall'] = 'This plugin cannot be uninstalled';
$string['plugininstalled'] = 'Plugin "{$a}" installed successfully';
$string['plugininstallfailed'] = 'Error installing plugin';
$string['pluginnofileuploaded'] = 'No file was uploaded';
$string['pluginupgraded'] = 'Plugin "{$a}" upgraded successfully';
$string['pluginupgradefailed'] = 'Error upgrading plugin';
$string['confirmpluginuninstall'] = 'Are you sure you want to uninstall this plugin? This action cannot be undone.';
$string['uninstall'] = 'Uninstall';
$string['installed'] = 'Installed';
$string['notinstalled'] = 'Not installed';
$string['upgraderequired'] = 'Upgrade required';
$string['dbversion'] = 'DB version';
$string['tools'] = 'Tools';
// Plugin install errors
$string['pluginzipnotfound'] = 'ZIP file not found';
$string['pluginzipopenfailed'] = 'Failed to open ZIP file';
$string['plugininvalidstructure'] = 'Invalid plugin structure in ZIP file';
$string['pluginversionnotfound'] = 'Plugin version.php not found';
$string['plugintypenotdetected'] = 'Could not detect plugin type from version.php. Make sure $plugin->component is defined.';
$string['plugintypemismatch'] = 'Plugin type mismatch: detected "{$a->detected}" but selected "{$a->selected}"';
$string['plugintypeinvalid'] = 'Invalid plugin type: {$a}';
$string['pluginmovefailed'] = 'Failed to move plugin files to target directory';

// Plugin types
$string['plugintype_report'] = 'Reports';
$string['plugintype_tool'] = 'Tools';
$string['plugintype_theme'] = 'Themes';
$string['plugintype_auth'] = 'Authentication';
$string['plugintype_block'] = 'Blocks';
