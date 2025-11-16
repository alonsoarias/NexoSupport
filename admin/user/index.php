<?php
/**
 * NexoSupport - User Management Interface
 *
 * Frankenstyle admin interface for user management
 * Integrates with core\user namespace and RBAC system
 *
 * @package    admin
 * @subpackage user
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Require system bootstrap
require_once __DIR__ . '/../../bootstrap.php';

// Define as internal access
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
}

// Load RBAC functions
require_once LIB_DIR . '/accesslib.php';

// Require login
require_login();

// Require user management capability
require_capability('users.view');

// Initialize database
use ISER\Core\Database\Database;
use ISER\Controllers\UserManagementController;

$database = Database::getInstance();

// Create controller instance
$controller = new UserManagementController($database);

// Get request from globals (simplified for direct access)
// In production, use PSR-7 request from router
$request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

// Handle different actions
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        $response = $controller->create($request);
        break;

    case 'edit':
        // For POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = $controller->edit($request);
        } else {
            // Redirect to index if GET
            header('Location: /admin/users');
            exit;
        }
        break;

    case 'index':
    default:
        $response = $controller->index($request);
        break;
}

// Output response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();
