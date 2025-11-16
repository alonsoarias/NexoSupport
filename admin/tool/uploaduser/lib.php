<?php
/**
 * NexoSupport - Upload Users Tool Library
 *
 * @package    tool_uploaduser
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get tool capabilities
 *
 * @return array Capabilities
 */
function tool_uploaduser_get_capabilities(): array
{
    return [
        'tool/uploaduser:upload' => [
            'name' => 'Upload users',
            'description' => 'Upload multiple users via CSV file',
            'module' => 'tool_uploaduser',
        ],
        'tool/uploaduser:view' => [
            'name' => 'View upload history',
            'description' => 'View user upload history and logs',
            'module' => 'tool_uploaduser',
        ],
    ];
}

/**
 * Get tool title
 *
 * @return string Tool title
 */
function tool_uploaduser_get_title(): string
{
    return __('Upload Users');
}

/**
 * Get tool description
 *
 * @return string Tool description
 */
function tool_uploaduser_get_description(): string
{
    return __('Upload multiple user accounts at once using a CSV file');
}

/**
 * Get required CSV columns
 *
 * @return array Required columns
 */
function tool_uploaduser_get_required_columns(): array
{
    return ['username', 'email', 'password'];
}

/**
 * Get optional CSV columns
 *
 * @return array Optional columns
 */
function tool_uploaduser_get_optional_columns(): array
{
    return ['firstname', 'lastname', 'status'];
}

/**
 * Get all valid CSV columns
 *
 * @return array All valid columns
 */
function tool_uploaduser_get_all_columns(): array
{
    return array_merge(
        tool_uploaduser_get_required_columns(),
        tool_uploaduser_get_optional_columns()
    );
}

/**
 * Validate user data from CSV
 *
 * @param array $data User data
 * @return array Errors (empty if valid)
 */
function tool_uploaduser_validate_user_data(array $data): array
{
    $errors = [];

    // Required fields
    if (empty($data['username'])) {
        $errors[] = 'Username is required';
    } elseif (strlen($data['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }

    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    // Optional fields validation
    if (isset($data['status']) && !in_array($data['status'], ['active', 'suspended', 'pending'])) {
        $errors[] = 'Invalid status. Must be: active, suspended, or pending';
    }

    return $errors;
}

/**
 * Format upload results for display
 *
 * @param array $results Upload results
 * @return string Formatted message
 */
function tool_uploaduser_format_results(array $results): string
{
    $total = $results['success'] + $results['errors'];
    $message = sprintf(
        'Processed %d row(s): %d successful, %d failed',
        $total,
        $results['success'],
        $results['errors']
    );

    return $message;
}

/**
 * Get menu items for this tool
 *
 * @return array Menu items
 */
function tool_uploaduser_get_menu_items(): array
{
    $items = [];

    if (has_capability('tool/uploaduser:upload')) {
        $items[] = [
            'title' => 'Upload Users',
            'url' => '/admin/tool/uploaduser',
            'icon' => 'upload',
            'active' => strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/tool/uploaduser') === 0,
        ];
    }

    return $items;
}
