<?php
/**
 * Configuration File
 *
 * This file should be required at the start of every script.
 * Similar to Moodle's config.php pattern.
 *
 * Usage in scripts:
 *   require_once(__DIR__ . '/../../config.php');
 *
 * @package NexoSupport
 */

// Prevent direct execution
if (!defined('BASE_DIR')) {
    define('BASE_DIR', __DIR__);
}

// Define internal constant for security
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
}

// Load system setup
require_once(BASE_DIR . '/lib/setup.php');
