<?php
/**
 * NexoSupport - Role Management Interface
 *
 * Frankenstyle admin interface for role and permission management
 * Integrates with core\role namespace and RBAC system
 *
 * @package    admin
 * @subpackage roles
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

// Require role management capability
require_capability('roles.view');

// Initialize database
use ISER\Core\Database\Database;
use ISER\Controllers\RoleController;

$database = Database::getInstance();

// Create controller instance
$controller = new RoleController($database);

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
            header('Location: /admin/roles');
            exit;
        }
        break;

    case 'permissions':
        // Manage permissions for a role
        require_capability('roles.assign_permissions');
        $response = $controller->managePermissions($request);
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
