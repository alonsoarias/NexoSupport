<?php

/**
 * Theme Preview System Translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    'preview' => [
        'title' => 'Theme Preview',
        'header' => 'Advanced Appearance Configuration with Preview',
        'description' => 'Select a theme, view changes in real-time, and apply your preference.',
        'available_themes' => 'Available Themes',
        'select_theme' => 'Select a theme to preview',
        'live_preview' => 'Live Preview',
        'preview' => 'Preview',
        'select' => 'Select',
        'current' => 'Current',
        'configuration' => 'Theme Configuration',
        'apply' => 'Apply Theme',
        'apply_confirm' => 'Are you sure you want to apply this theme to your account?',
        'apply_success' => 'Theme applied successfully. You will be redirected shortly.',
        'cancel' => 'Cancel',
        'cancel_confirm' => 'Do you want to exit without applying changes?',
        'reset' => 'Reset',
        'reset_confirm' => 'Do you want to reset to your original theme?',
        'reset_success' => 'Preview reset to your original theme.',
    ],

    'switch_success' => 'Theme switched successfully. View the live preview.',

    'errors' => [
        'unauthorized' => 'Unauthorized. You must be logged in to access this feature.',
        'forbidden' => 'Access denied. Only administrators can change themes.',
        'not_found' => 'The requested theme does not exist.',
        'invalid_request' => 'Invalid request. Please check the provided data.',
        'internal' => 'Internal server error. Please try again later.',
        'no_preview' => 'No theme in preview to apply.',
        'no_user' => 'Invalid user session.',
        'save_failed' => 'Failed to save theme preference. Please try again.',
    ],

    'metadata' => [
        'author' => 'Author',
        'version' => 'Version',
        'description' => 'Description',
    ],

    // Configuration sections (inherited from settings.php)
    'groups' => [
        'colors' => 'Colors',
        'typography' => 'Typography',
        'layouts' => 'Layouts',
    ],
];
