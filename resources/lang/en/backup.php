<?php

/**
 * Database Backups Module Translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // Page titles
    'page_title' => 'Database Backups',
    'title' => 'Database Backup Management',

    // Actions
    'create_backup' => 'Create Backup',
    'backup_list' => 'Backup List',
    'download' => 'Download',
    'delete' => 'Delete',

    // Table columns
    'filename' => 'Filename',
    'size' => 'Size',
    'date' => 'Creation Date',
    'actions' => 'Actions',

    // Messages
    'no_backups_yet' => 'No backups yet. Create a new one to get started.',
    'backup_created_success' => 'Backup created successfully',
    'backup_creation_failed' => 'Failed to create backup',
    'backup_deleted_success' => 'Backup deleted successfully',
    'backup_deletion_failed' => 'Failed to delete backup',
    'error_listing_backups' => 'Error listing backups',
    'error_creating_backup' => 'Error creating backup',

    // UI Labels
    'creating_backup' => 'Creating backup... Please wait...',
    'backup_dir_warning' => 'Warning: The backup directory does not have write permissions',
    'total_backup_size' => 'Total backup size',

    // Warnings and Instructions
    'warning_restore' => 'Security Warning',
    'restore_instructions' => 'Restoring backups is a potentially dangerous operation that requires direct access to the server command line. A web interface is not provided for this operation for security reasons. If you need to restore a backup, contact your server administrator.',

    // Backup info
    'backup_info' => 'Backup Information',
    'backup_directory' => 'Backup Directory',
    'backup_location' => 'Location: :path',
    'backup_permissions' => 'Directory Permissions',
    'writable' => 'Writable',
    'not_writable' => 'Not writable',

    // Success messages
    'success' => [
        'backup_created' => 'Backup created successfully',
        'backup_downloaded' => 'Backup download started',
        'backup_deleted' => 'Backup deleted successfully',
    ],

    // Error messages
    'errors' => [
        'backup_creation_failed' => 'Failed to create backup',
        'backup_download_failed' => 'Failed to download backup',
        'backup_deletion_failed' => 'Failed to delete backup',
        'invalid_backup_file' => 'Invalid backup file',
        'backup_directory_not_writable' => 'Backup directory does not have write permissions',
        'insufficient_disk_space' => 'Insufficient disk space',
    ],
];
