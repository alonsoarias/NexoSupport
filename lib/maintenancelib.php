<?php
/**
 * Maintenance Mode Library
 *
 * Functions for handling site maintenance mode.
 * Similar to Moodle's maintenance mode handling.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Check if site is in maintenance mode
 *
 * Checks both database and CLI maintenance modes.
 *
 * @return bool True if site is in maintenance mode
 */
function is_maintenance_mode(): bool {
    global $CFG;

    // Check CLI maintenance flag file first (highest priority)
    if (!empty($CFG->dataroot)) {
        $climaintfile = $CFG->dataroot . '/climaintenance.html';
        if (file_exists($climaintfile)) {
            return true;
        }
    }

    // Check database setting
    return (bool)get_config('core', 'maintenance_enabled');
}

/**
 * Enable maintenance mode
 *
 * @param string $message Optional maintenance message
 * @return bool True on success
 */
function enable_maintenance_mode(string $message = ''): bool {
    global $CFG;

    set_config('maintenance_enabled', 1, 'core');

    if (!empty($message)) {
        set_config('maintenance_message', $message, 'core');
    }

    // Log the event
    if (function_exists('debugging')) {
        debugging('Maintenance mode enabled', DEBUG_DEVELOPER);
    }

    return true;
}

/**
 * Disable maintenance mode
 *
 * @return bool True on success
 */
function disable_maintenance_mode(): bool {
    global $CFG;

    set_config('maintenance_enabled', 0, 'core');

    // Remove CLI maintenance file if it exists
    if (!empty($CFG->dataroot)) {
        $climaintfile = $CFG->dataroot . '/climaintenance.html';
        if (file_exists($climaintfile)) {
            @unlink($climaintfile);
        }
    }

    // Log the event
    if (function_exists('debugging')) {
        debugging('Maintenance mode disabled', DEBUG_DEVELOPER);
    }

    return true;
}

/**
 * Get the maintenance message
 *
 * @return string Maintenance message
 */
function get_maintenance_message(): string {
    global $CFG;

    // Check for CLI maintenance file content
    if (!empty($CFG->dataroot)) {
        $climaintfile = $CFG->dataroot . '/climaintenance.html';
        if (file_exists($climaintfile)) {
            $content = file_get_contents($climaintfile);
            if ($content !== false) {
                return $content;
            }
        }
    }

    // Get database message
    $message = get_config('core', 'maintenance_message');
    if (!empty($message)) {
        return $message;
    }

    // Return default message
    if (function_exists('get_string')) {
        return get_string('sitemaintenancewarning', 'admin');
    }

    return 'This site is currently undergoing maintenance and will be back soon.';
}

/**
 * Enable CLI maintenance mode
 *
 * Creates a maintenance file in dataroot that bypasses database.
 * Useful during database upgrades when the database is not accessible.
 *
 * @param string $message HTML content to show
 * @return bool True on success
 */
function enable_cli_maintenance_mode(string $message = ''): bool {
    global $CFG;

    if (empty($CFG->dataroot)) {
        return false;
    }

    $climaintfile = $CFG->dataroot . '/climaintenance.html';

    if (empty($message)) {
        $message = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .maintenance-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        h1 { color: #333; margin-bottom: 1rem; }
        p { color: #666; line-height: 1.6; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="icon">&#128679;</div>
        <h1>Site Under Maintenance</h1>
        <p>We are currently performing scheduled maintenance. Please check back soon.</p>
    </div>
</body>
</html>';
    }

    return file_put_contents($climaintfile, $message) !== false;
}

/**
 * Disable CLI maintenance mode
 *
 * Removes the maintenance file from dataroot.
 *
 * @return bool True on success
 */
function disable_cli_maintenance_mode(): bool {
    global $CFG;

    if (empty($CFG->dataroot)) {
        return false;
    }

    $climaintfile = $CFG->dataroot . '/climaintenance.html';

    if (file_exists($climaintfile)) {
        return @unlink($climaintfile);
    }

    return true;
}

/**
 * Check if the current user can bypass maintenance mode
 *
 * Site administrators can always bypass maintenance mode.
 *
 * @return bool True if user can bypass
 */
function can_bypass_maintenance(): bool {
    global $USER;

    // Not logged in - cannot bypass
    if (empty($USER->id)) {
        return false;
    }

    // Check if user is site admin
    if (function_exists('is_siteadmin')) {
        return is_siteadmin($USER->id);
    }

    // Check using has_capability if available
    if (function_exists('has_capability')) {
        return has_capability('nexosupport/site:config', \core\context\context_system::instance(), $USER->id);
    }

    return false;
}

/**
 * Display maintenance page and exit
 *
 * Renders the maintenance mode page and terminates execution.
 *
 * @param string|null $message Custom message (uses default if null)
 * @return void
 */
function display_maintenance_page(?string $message = null): void {
    global $CFG;

    // Check for CLI maintenance file first
    if (!empty($CFG->dataroot)) {
        $climaintfile = $CFG->dataroot . '/climaintenance.html';
        if (file_exists($climaintfile)) {
            readfile($climaintfile);
            exit;
        }
    }

    // Get message if not provided
    if ($message === null) {
        $message = get_maintenance_message();
    }

    // Set appropriate headers
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 300'); // Suggest retry in 5 minutes

    // Output page
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .maintenance-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .maintenance-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 1rem;
        }
        .message {
            color: #555;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 1rem;
        }
        .progress-fill {
            width: 30%;
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            animation: progress 2s ease-in-out infinite;
        }
        @keyframes progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }
        .footer-text {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <span class="maintenance-icon">&#128736;</span>
        <h1>Site Under Maintenance</h1>
        <div class="message">' . htmlspecialchars($message) . '</div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <p class="footer-text">We\'ll be back shortly. Thank you for your patience.</p>
    </div>
</body>
</html>';

    exit;
}

/**
 * Handle maintenance mode check
 *
 * This is the main function to call from the front controller.
 * Checks if maintenance is enabled and handles the response.
 *
 * @param string $uri Current request URI
 * @return void
 */
function check_maintenance_mode(string $uri): void {
    // Skip check for certain paths
    $excluded_paths = [
        '/login',
        '/logout',
        '/admin/settings/maintenancemode',
    ];

    foreach ($excluded_paths as $path) {
        if ($uri === $path || strpos($uri, $path) === 0) {
            return;
        }
    }

    // Check if site is in maintenance mode
    if (!is_maintenance_mode()) {
        return;
    }

    // Check if user can bypass
    if (can_bypass_maintenance()) {
        // User can bypass, but show a warning
        if (function_exists('debugging')) {
            debugging('Site is in maintenance mode', DEBUG_DEVELOPER);
        }
        return;
    }

    // Display maintenance page
    display_maintenance_page();
}
