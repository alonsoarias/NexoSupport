<?php
/**
 * ISER Authentication System - Security Check
 *
 * Common security verification for legacy admin files
 * This file should be included at the top of all admin legacy files
 *
 * @package    ISER\Core\Security
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Prevent direct access to this security check file
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Direct access to this file is not allowed.</p>');
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify user is authenticated
if (!isset($_SESSION['user_id']) ||
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true) {

    // If accessed via AJAX, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Otherwise redirect to login
    header('Location: /login');
    exit;
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Set security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Define BASE_DIR if not already defined (for compatibility)
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}
