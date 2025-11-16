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
// 3. SERVE STATIC ASSETS
// ========================================

/**
 * Serve static files from resources/assets/public/
 * This allows keeping public_html/ clean with only index.php
 */
function serveStaticAsset(): void
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $parsedUrl = parse_url($requestUri);
    $path = $parsedUrl['path'] ?? '';

    // Only handle /assets/* requests
    if (strpos($path, '/assets/') !== 0) {
        return;
    }

    // Remove /assets/ prefix to get relative path
    $relativePath = substr($path, strlen('/assets/'));

    // Prevent directory traversal attacks
    if (strpos($relativePath, '..') !== false || strpos($relativePath, './') !== false) {
        http_response_code(400);
        exit('Invalid path');
    }

    // Build absolute file path
    $filePath = BASE_DIR . '/resources/assets/public/' . $relativePath;

    // Check if file exists and is readable
    if (!file_exists($filePath) || !is_file($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        exit('Asset not found');
    }

    // Determine MIME type
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));

    // Cache headers for static assets (1 year for images/fonts, 1 month for CSS/JS)
    if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'eot'])) {
        header('Cache-Control: public, max-age=31536000, immutable');
    } else {
        header('Cache-Control: public, max-age=2592000');
    }

    // Output file
    readfile($filePath);
    exit;
}

// Serve static assets if requested
serveStaticAsset();

// ========================================
// 4. LOAD AUTOLOADER
// ========================================

if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('<h1>Dependency Error</h1><p>Composer dependencies not installed. Run: composer install</p>');
}

require_once BASE_DIR . '/vendor/autoload.php';

// ========================================
// 5. LOAD SYSTEM SETUP
// ========================================

require_once BASE_DIR . '/lib/setup.php';

// ========================================
// 6. START SESSION
// ========================================

session_start();

// ========================================
// 7. INITIALIZE APPLICATION
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
// 8. GET DATABASE INSTANCE
// ========================================

$database = $app->getDatabase();

// ========================================
// 9. CREATE ROUTER
// ========================================

$router = new Router();

// ========================================
// 10. LOAD ROUTE CONFIGURATIONS
// ========================================

require BASE_DIR . '/config/routes.php';          // Public and protected routes
require BASE_DIR . '/config/routes/admin.php';    // Admin routes
require BASE_DIR . '/config/routes/api.php';      // API routes

// ========================================
// 11. DISPATCH REQUEST
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
