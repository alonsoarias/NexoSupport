<?php

/**
 * ISER Authentication System - Main Entry Point
 *
 * This is the main entry point for the ISER Authentication System.
 *
 * @package    ISER
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

// Define base directory
define('ISER_BASE_DIR', dirname(__DIR__));

// Load Composer autoloader
require_once ISER_BASE_DIR . '/vendor/autoload.php';

// Import Bootstrap class
use ISER\Core\Bootstrap;
use ISER\Core\Utils\Logger;
use ISER\Core\Utils\Helpers;

// Error handling for production
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (Logger::isInitialized()) {
        Logger::error('PHP Error', [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
        ]);
    }

    // Don't execute PHP internal error handler
    return true;
});

set_exception_handler(function ($exception) {
    if (Logger::isInitialized()) {
        Logger::exception($exception);
    }

    http_response_code(500);

    // Check if we should display errors
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<h1>Exception</h1>';
        echo '<pre>' . $exception->getMessage() . '</pre>';
        echo '<pre>' . $exception->getTraceAsString() . '</pre>';
    } else {
        echo '<h1>System Error</h1>';
        echo '<p>An error occurred. Please contact the administrator.</p>';
    }
});

try {
    // Initialize the system
    $app = new Bootstrap(ISER_BASE_DIR);
    $app->init();

    // Get router
    $router = $app->getRouter();

    // Define routes
    $router->get('/', function () use ($app) {
        $systemInfo = $app->getSystemInfo();

        // Basic HTML output for Phase 1
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema ISER - En desarrollo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
        }

        .status {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }

        .status-label {
            font-weight: 600;
            color: #333;
        }

        .status-value {
            color: #666;
            font-family: 'Courier New', monospace;
        }

        .success {
            color: #10b981;
            font-weight: 600;
        }

        .links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
        }

        .link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin: 5px;
            transition: background 0.3s;
        }

        .link:hover {
            background: #5568d3;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema ISER</h1>
        <p class="subtitle">Sistema de Autenticación - Fase 1</p>

        <div class="status">
            <div class="status-item">
                <span class="status-label">Estado:</span>
                <span class="success">✓ En desarrollo</span>
            </div>
            <div class="status-item">
                <span class="status-label">Versión:</span>
                <span class="status-value">{$systemInfo['version']}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Entorno:</span>
                <span class="status-value">{$systemInfo['environment']}</span>
            </div>
            <div class="status-item">
                <span class="status-label">PHP:</span>
                <span class="status-value">{$systemInfo['php_version']}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Módulos detectados:</span>
                <span class="status-value">{$systemInfo['modules_count']}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Sistema inicializado:</span>
                <span class="success">✓ Sí</span>
            </div>
        </div>

        <div class="links">
            <a href="/login.php" class="link">Iniciar sesión</a>
            <a href="/admin.php" class="link">Administración</a>
        </div>

        <div class="footer">
            <p>ISER Authentication System &copy; 2024</p>
            <p>Fase 1: Núcleo del Sistema Completada</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }, 'home');

    // API endpoint for system info (JSON)
    $router->get('/api/system-info', function () use ($app) {
        header('Content-Type: application/json');
        return $app->getSystemInfo();
    }, 'api.system-info');

    // API health check
    $router->get('/api/health', function () use ($app) {
        $database = $app->getDatabase();
        $dbStatus = $database ? $database->testConnection() : false;

        return [
            'status' => 'ok',
            'timestamp' => time(),
            'database' => $dbStatus ? 'connected' : 'disconnected',
        ];
    }, 'api.health');

    // Run the application
    $app->run();

} catch (\Exception $e) {
    // Fallback error handling
    http_response_code(500);

    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'System initialization failed',
        ]);
    } else {
        echo '<h1>System Error</h1>';
        echo '<p>Failed to initialize the system. Please check the logs.</p>';
    }

    exit(1);
}
