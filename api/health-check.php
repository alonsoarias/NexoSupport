<?php
/**
 * NexoSupport - Health Check API Endpoint
 *
 * Simple JSON endpoint for external monitoring tools
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

require_once __DIR__ . '/../lib/setup.php';
require_once __DIR__ . '/../lib/classes/health/health_checker.php';

use ISER\Core\Health\HealthChecker;

header('Content-Type: application/json');

try {
    $checks = HealthChecker::run_all_checks();

    $response = [
        'status' => $checks['overall']['status'],
        'timestamp' => date('c'),
        'checks' => [
            'database' => $checks['database']['status'],
            'filesystem' => $checks['filesystem']['status'],
            'php_extensions' => $checks['php']['status'],
            'disk_space' => $checks['disk']['status'],
            'cache' => $checks['cache']['status'],
        ],
        'message' => $checks['overall']['message'],
    ];

    http_response_code($checks['overall']['status'] === 'ok' ? 200 : 503);
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Health check failed: ' . $e->getMessage(),
        'timestamp' => date('c'),
    ], JSON_PRETTY_PRINT);
}
