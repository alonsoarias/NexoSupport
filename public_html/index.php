<?php
/**
 * NexoSupport - Front Controller (Frankenstyle Architecture)
 *
 * Single entry point for all requests - Simplified and clean
 *
 * @package    NexoSupport
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 */

declare(strict_types=1);

// ========================================
// 1. DEFINE BASE CONSTANTS
// ========================================

define('BASE_DIR', dirname(__DIR__));
define('ENV_FILE', BASE_DIR . '/.env');
define('NEXOSUPPORT_INTERNAL', true);

// ========================================
// 2. VERIFY INSTALLATION
// ========================================

function checkInstallation(): bool
{
    if (!file_exists(ENV_FILE)) {
        return false;
    }

    $envContent = @file_get_contents(ENV_FILE);
    if ($envContent === false) {
        return false;
    }

    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        if (strpos($line, 'INSTALLED=') === 0) {
            $value = trim(str_replace('INSTALLED=', '', $line));
            return ($value === 'true');
        }
    }

    return false;
}

if (!checkInstallation()) {
    header('Location: /install.php');
    exit;
}

// ========================================
// 3. LOAD AUTOLOADER
// ========================================

if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('<h1>Dependency Error</h1><p>Composer dependencies not installed. Run: composer install</p>');
}

require_once BASE_DIR . '/vendor/autoload.php';

// ========================================
// 4. LOAD SYSTEM SETUP
// ========================================

require_once BASE_DIR . '/lib/setup.php';

// ========================================
// 5. START SESSION
// ========================================

session_start();

// ========================================
// 6. INITIALIZE APPLICATION
// ========================================

use ISER\Core\Bootstrap;
use ISER\Core\Routing\Router;
use ISER\Core\Routing\RouteNotFoundException;
use ISER\Core\Http\Request;
use ISER\Core\Http\Response;

try {
    $app = new Bootstrap(BASE_DIR);
    $app->init();
} catch (Exception $e) {
    error_log('Bootstrap Error: ' . $e->getMessage());
    http_response_code(500);
    die('<h1>System Error</h1><p>Failed to initialize the application.</p>');
}

// ========================================
// 7. GET DATABASE INSTANCE
// ========================================

$database = $app->getDatabase();

// ========================================
// 8. CREATE ROUTER
// ========================================

$router = new Router();

// ========================================
// 9. LOAD ROUTE CONFIGURATIONS
// ========================================

require BASE_DIR . '/config/routes.php';          // Public and protected routes
require BASE_DIR . '/config/routes/admin.php';    // Admin routes
require BASE_DIR . '/config/routes/api.php';      // API routes

// ========================================
// 10. DISPATCH REQUEST
// ========================================

try {
    $request = Request::createFromGlobals();
    $response = $router->dispatch($request);
    $response->send();
} catch (RouteNotFoundException $e) {
    http_response_code(404);
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The requested URL was not found on this server.</p>';
} catch (Exception $e) {
    error_log('Routing Error: ' . $e->getMessage());
    http_response_code(500);
    echo '<h1>500 - Internal Server Error</h1>';
}
