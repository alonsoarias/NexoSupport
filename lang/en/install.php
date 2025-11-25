<?php
/**
 * Language strings - Installer - English
 *
 * @package core
 * @subpackage install
 * @copyright NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Stage indicators
$string['step'] = 'Step {$a->current} of {$a->total}';
$string['stage_welcome'] = 'Welcome';
$string['stage_requirements'] = 'System Requirements';
$string['stage_database'] = 'Database Configuration';
$string['stage_install_db'] = 'Database Installation';
$string['stage_admin'] = 'Create Administrator User';
$string['stage_finish'] = 'Finish Installation';

// Welcome stage
$string['welcome_title'] = 'NexoSupport';
$string['welcome_subtitle'] = 'System Installation v{$a}';
$string['welcome_message'] = 'Welcome to NexoSupport';
$string['welcome_description'] = 'This wizard will guide you through the installation process.';
$string['about_nexosupport'] = 'NexoSupport is a management system built with Moodle Frankenstyle architecture.';
$string['features_title'] = 'Features:';
$string['feature_plugins'] = 'Extensible plugin system';
$string['feature_rbac'] = 'Role-based access control (RBAC)';
$string['feature_navigation'] = 'Modern navigation with Font Awesome 6';
$string['feature_cache'] = 'Advanced cache system (OPcache, i18n, Mustache)';
$string['feature_templates'] = 'Mustache templates for customization';
$string['feature_themes'] = 'Customizable theme system';
$string['button_start'] = 'Start installation';

// Requirements stage
$string['requirements_title'] = 'Requirements Check';
$string['requirements_subtitle'] = 'Checking that the server meets minimum requirements';
$string['requirements_success'] = 'Perfect! Your server meets all requirements.';
$string['requirements_error'] = 'Your server does not meet some requirements. Please fix the issues before continuing.';
$string['requirement_phpversion'] = 'PHP Version >= {$a}';
$string['requirement_pdo'] = 'PDO Extension';
$string['requirement_pdo_mysql'] = 'PDO MySQL Driver';
$string['requirement_pdo_pgsql'] = 'PDO PostgreSQL Driver';
$string['requirement_json'] = 'JSON Extension';
$string['requirement_mbstring'] = 'mbstring Extension';
$string['requirement_writable'] = 'Writable: {$a}';
$string['status_installed'] = 'Installed';
$string['status_not_installed'] = 'Not installed';
$string['status_writable'] = 'Writable';
$string['status_not_writable'] = 'Not writable';
$string['button_check_again'] = 'Check again';

// Database stage
$string['database_title'] = 'Database Configuration';
$string['database_subtitle'] = 'Configure the database connection';
$string['database_info'] = 'The .env file will be automatically generated with the provided configuration. The database will be created if it does not exist (MySQL only).';
$string['database_driver'] = 'Database Driver';
$string['database_driver_mysql'] = 'MySQL / MariaDB';
$string['database_driver_pgsql'] = 'PostgreSQL';
$string['database_host'] = 'Host';
$string['database_host_help'] = 'Usually "localhost" or "127.0.0.1"';
$string['database_name'] = 'Database Name';
$string['database_name_help'] = 'Only letters, numbers and underscores';
$string['database_user'] = 'User';
$string['database_password'] = 'Password';
$string['database_password_help'] = 'Leave blank if there is no password';
$string['database_prefix'] = 'Table Prefix';
$string['database_prefix_help'] = 'Prefix for all tables (e.g., nxs_). Only letters, numbers and underscores.';
$string['button_test_connection'] = 'Test Connection and Continue';

// Install DB stage
$string['installdb_title'] = 'Database Installation';
$string['installdb_subtitle'] = 'Creating system tables';
$string['installdb_installing'] = 'Installing...';
$string['installdb_installing_message'] = 'Please wait while system tables are being created.';
$string['installdb_success'] = 'Database installed successfully';
$string['installdb_error_title'] = 'Installation Error';
$string['installdb_log_title'] = 'Installation log:';
$string['button_back_database'] = 'Back to Database Configuration';

// Admin stage
$string['admin_title'] = 'Create Administrator User';
$string['admin_subtitle'] = 'Configure the system administrator account';
$string['admin_important'] = 'Important:';
$string['admin_important_message'] = 'This account will have full access to the system. Make sure to use a strong password.';
$string['admin_username'] = 'Username';
$string['admin_username_help'] = 'Only letters, numbers, hyphens, dots and underscores';
$string['admin_email'] = 'Email';
$string['admin_firstname'] = 'First Name';
$string['admin_lastname'] = 'Last Name';
$string['admin_password'] = 'Password';
$string['admin_password_help'] = 'Minimum 8 characters';
$string['admin_password_confirm'] = 'Confirm Password';
$string['admin_password_mismatch'] = 'Passwords do not match';
$string['button_create_admin'] = 'Create Administrator';

// Finish stage
$string['finish_title'] = 'Finishing Installation';
$string['finish_subtitle'] = 'Setting up RBAC system and completing installation';
$string['finish_processing'] = 'Processing...';
$string['finish_processing_message'] = 'Installing roles and permissions system, configuring system...';
$string['finish_complete_title'] = 'Installation Complete!';
$string['finish_complete_subtitle'] = 'NexoSupport is ready to use';
$string['finish_congratulations'] = 'Congratulations!';
$string['finish_congratulations_message'] = 'NexoSupport has been installed successfully.';
$string['finish_tasks_title'] = 'Completed tasks:';
$string['finish_nextsteps_title'] = 'Next steps:';
$string['finish_nextstep_1'] = 'Log in with your administrator account';
$string['finish_nextstep_2'] = 'Configure the system from the administration panel';
$string['finish_nextstep_3'] = 'Create users and assign roles';
$string['finish_nextstep_4'] = 'Customize the theme and appearance';
$string['finish_error_title'] = 'Finalization Error';
$string['button_go_system'] = 'Go to System';

// Common buttons
$string['button_back'] = 'Back';
$string['button_continue'] = 'Continue';
$string['button_next'] = 'Next';
$string['button_cancel'] = 'Cancel';

// Common messages
$string['error'] = 'Error';
$string['information'] = 'Information';
$string['warning'] = 'Warning';
$string['attention'] = 'Attention';
$string['perfect'] = 'Perfect!';

// Installer class - Validation messages
$string['installer_invalid_driver'] = 'Invalid database driver';
$string['installer_invalid_dbname'] = 'Invalid database name (only letters, numbers and underscores)';
$string['installer_invalid_prefix'] = 'Invalid table prefix (only letters, numbers and underscores)';
$string['installer_required_fields'] = 'Required fields missing';
$string['installer_dbconfig_not_found'] = 'Database configuration not found';
$string['installer_existing_installation'] = 'An installation already exists in this database';
$string['installer_invalid_email'] = 'Invalid email';
$string['installer_password_too_short'] = 'Password must be at least 8 characters long';
$string['installer_incomplete_data'] = 'Incomplete installation data';

// Installer class - Log messages
$string['installer_log_connected'] = 'Database connection established';
$string['installer_log_empty_db'] = 'Empty database, proceeding with installation';
$string['installer_log_installing_schema'] = 'Installing schema from lib/db/install.xml';
$string['installer_log_schema_installed'] = 'Schema installed successfully';
$string['installer_log_system_context'] = 'SYSTEM context created';
$string['installer_log_rbac_installing'] = 'Installing RBAC system from lib/db/rbac.php';
$string['installer_log_rbac_installed'] = 'RBAC system installed';
$string['installer_log_role_assigned'] = 'Role \'{$a}\' assigned to administrator';
$string['installer_log_config_created'] = 'Initial configuration created';
$string['installer_log_version_set'] = 'System version set: {$a}';
$string['installer_log_installation_complete'] = 'Installation completed successfully';

// Upgrader class - Messages
$string['upgrader_no_upgrade_needed'] = 'No upgrade needed';
$string['upgrader_upgrade_required'] = 'Upgrade required from v{$a->current} to v{$a->target}';
$string['upgrader_requirements_failed'] = 'Requirements not met';
$string['upgrader_no_db'] = 'Database not available';
$string['upgrader_executing'] = 'Executing upgrade from v{$a->from} to v{$a->to}';
$string['upgrader_success'] = 'Upgrade completed successfully';
$string['upgrader_failed'] = 'Error during upgrade: {$a}';
$string['upgrader_log_start'] = 'Starting upgrade from version {$a}';
$string['upgrader_log_requirements'] = 'Checking pre-requisites';
$string['upgrader_log_backup'] = 'IMPORTANT: It is recommended to back up the database';
$string['upgrader_log_executing'] = 'Executing updates';
$string['upgrader_log_purging'] = 'Purging system caches';
$string['upgrader_log_complete'] = 'Upgrade completed';
$string['upgrader_log_version_updated'] = 'Version updated to {$a}';
$string['upgrader_log_plugins'] = 'Processing plugins';
$string['upgrader_plugins_uptodate'] = 'All plugins are up to date';
$string['upgrader_plugin_installing'] = 'Installing plugin: {$a}';
$string['upgrader_plugin_upgrading'] = 'Upgrading plugin: {$a}';
$string['upgrader_plugin_failed'] = 'Failed to process plugin: {$a}';
$string['upgrader_plugins_error'] = 'Error processing plugins';
