<?php

/**
 * Plugin management translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Titles
    'management_title' => 'Plugin Management',
    'list_title' => 'Plugins List',
    'details_title' => 'Plugin Details',

    // Fields
    'name' => 'Name',
    'slug' => 'Slug',
    'version' => 'Version',
    'author' => 'Author',
    'description' => 'Description',
    'type' => 'Type',
    'status' => 'Status',
    'dependencies' => 'Dependencies',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'is_core' => 'Core Plugin',

    // Actions
    'install_button' => 'Install Plugin',
    'uninstall_button' => 'Uninstall',
    'enable_button' => 'Enable',
    'disable_button' => 'Disable',
    'upload_button' => 'Upload New Plugin',
    'discover_button' => 'Discover Plugins',
    'view_details_button' => 'View Details',
    'back_to_plugins' => 'Back to Plugins',

    // Messages
    'installed_message' => 'Plugin installed successfully',
    'uninstalled_message' => 'Plugin uninstalled successfully',
    'enabled_message' => 'Plugin enabled successfully',
    'disabled_message' => 'Plugin disabled successfully',
    'plugin_not_found' => 'Plugin not found',

    // Confirmations
    'confirm_uninstall' => 'Are you sure you want to uninstall this plugin? This action cannot be undone.',
    'confirm_disable' => 'Are you sure you want to disable this plugin?',
    'confirm_enable' => 'Enable this plugin?',

    // Filters
    'filter_all_status' => 'All Status',
    'filter_enabled' => 'Enabled',
    'filter_disabled' => 'Disabled',
    'filter_by_type' => 'Filter by Type',
    'search_plugins' => 'Search plugins...',

    // Plugin Types
    'type_auth' => 'Authentication',
    'type_theme' => 'Theme',
    'type_tool' => 'Tool',
    'type_module' => 'Module',
    'type_integration' => 'Integration',
    'type_report' => 'Report',

    // Statistics
    'total_plugins' => 'Total Plugins',
    'enabled_count' => 'Enabled',
    'disabled_count' => 'Disabled',
    'by_type' => 'By Type',

    // No Data
    'no_plugins_found' => 'No plugins found',
    'no_dependencies' => 'No dependencies',
    'no_dependents' => 'No dependent plugins',

    // Settings
    'plugin_settings' => 'Plugin Settings',
    'no_settings' => 'This plugin has no configurable settings',

    // Plugin Upload
    'upload_title' => 'Upload New Plugin',
    'upload_description' => 'Install a new plugin by uploading a valid ZIP file',
    'upload_form_title' => 'Upload Form',
    'drag_drop_title' => 'Drag and drop ZIP file here',
    'or' => 'or',
    'browse_button' => 'Browse Files',

    // Instructions
    'instructions_title' => 'Instructions',
    'instruction_1' => 'File must be in ZIP format',
    'instruction_2' => 'ZIP must contain a valid plugin.json file at the root',
    'instruction_3' => 'Verify that the plugin is compatible with this system version',
    'instruction_4' => 'Maximum file size is 100MB',

    // Requirements
    'requirements_title' => 'Plugin Requirements',
    'requirement_1' => 'Valid plugin.json file structure',
    'requirement_2' => 'Unique slug (cannot exist another plugin with the same slug)',
    'requirement_3' => 'Valid type: tool, auth, theme, report, module, integration',
    'requirement_4' => 'Semantic versioning format (e.g., 1.0.0)',

    // Manifest
    'manifest_title' => 'Manifest Structure',
    'manifest_description' => 'The plugin.json file must contain at least these fields:',

    // Types
    'types_title' => 'Plugin Types',

    // Upload Messages
    'uploading' => 'Uploading file',
    'installation_complete' => 'Installation complete',
    'error_invalid_file' => 'Error: File must be a valid ZIP',
    'error_file_too_large' => 'Error: File exceeds maximum size of 100MB',
    'error_installation' => 'Error during installation',
    'error_upload' => 'Error uploading file. Invalid HTTP status code.',
    'error_network' => 'Network error. Check your connection and try again.',

    // Plugin Update
    'update_available' => 'Update available',
    'update_button' => 'Update',
    'current_version' => 'Current version',
    'new_version' => 'New version',
    'update_message' => 'Plugin updated successfully',
    'confirm_update' => 'Update this plugin to version {version}?',
];
