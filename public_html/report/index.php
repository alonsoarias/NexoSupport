<?php
/**
 * Página principal del sistema de reportes
 * @package report
 */

require_once(__DIR__ . '/../../core/bootstrap.php');

use ISER\Core\Database\Database;
use ISER\Core\Config\ConfigManager;
use ISER\Core\Config\SettingsManager;
use ISER\Modules\Auth\Middleware\AuthMiddleware;
use ISER\Modules\Admin\Middleware\AdminMiddleware;
use ISER\Modules\Report\Log\ReportLog;
use ISER\Modules\Report\Log\LogManager;
use ISER\Modules\Report\Log\LogExporter;
use ISER\Modules\Theme\Iser\ThemeIser;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Configuración
$config = ConfigManager::getInstance();
$db = Database::getInstance(
    $config->get('DB_HOST'),
    $config->get('DB_NAME'),
    $config->get('DB_USER'),
    $config->get('DB_PASS')
);

// Logger
$logger = new Logger('report');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../../var/logs/report.log', Logger::DEBUG));

$settings = new SettingsManager($db, $logger);

// Middleware de autenticación
$authMiddleware = new AuthMiddleware($db, $logger);
$adminMiddleware = new AdminMiddleware($db, $logger);

// Verificar autenticación
$authMiddleware->requireAuth();
$userId = $_SESSION['user_id'] ?? null;

// Verificar permisos de admin (solo admins pueden ver reportes completos)
$isAdmin = $adminMiddleware->isAdmin();

// Inicializar módulos
$reportLog = new ReportLog($db, $logger);
$logManager = new LogManager($db, $logger, $reportLog);
$exporter = new LogExporter($db, $logger, $reportLog);

// Inicializar tema
$theme = new ThemeIser($db, $settings, $logger, $userId);
$theme->init();

// Manejar acciones
$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'logs':
        showLogs($reportLog, $theme, $isAdmin, $userId);
        break;

    case 'security':
        showSecurityAlerts($logManager, $theme, $isAdmin);
        break;

    case 'export':
        handleExport($exporter, $reportLog, $logManager);
        break;

    case 'dashboard':
    default:
        showDashboard($reportLog, $logManager, $theme, $isAdmin, $userId);
        break;
}

/**
 * Mostrar dashboard de reportes
 */
function showDashboard($reportLog, $logManager, $theme, $isAdmin, $userId) {
    // Estadísticas generales
    $filters = $isAdmin ? [] : ['userid' => $userId];
    $filters['date_from'] = strtotime('-30 days');

    $stats = $reportLog->getStatistics($filters);
    $dailyStats = $reportLog->getDailyStats(
        date('Y-m-d', strtotime('-30 days')),
        date('Y-m-d'),
        null
    );

    // Actividad reciente
    $recentLogs = $reportLog->getLogs($filters, 1, 10);

    // Alertas de seguridad (solo admins)
    $alerts = [];
    if ($isAdmin) {
        $alertResult = $logManager->getSecurityAlerts(['status' => 'new'], 1, 5);
        $alerts = $alertResult['alerts'];
    }

    // Preparar datos para Chart.js
    $chartData = [
        'by_severity' => [],
        'by_component' => [],
        'daily_activity' => []
    ];

    foreach ($stats['by_severity'] as $item) {
        $chartData['by_severity'][] = [
            'label' => getSeverityLabel($item['severity']),
            'value' => $item['count']
        ];
    }

    foreach ($stats['by_component'] as $item) {
        $chartData['by_component'][] = [
            'label' => $item['component'],
            'value' => $item['count']
        ];
    }

    foreach ($dailyStats as $item) {
        $chartData['daily_activity'][] = [
            'date' => $item['stat_date'],
            'value' => $item['stat_value']
        ];
    }

    $data = [
        'page_title' => 'Dashboard de Reportes',
        'stats' => $stats,
        'recent_logs' => $recentLogs['logs'],
        'alerts' => $alerts,
        'chart_data' => json_encode($chartData),
        'is_admin' => $isAdmin
    ];

    echo $theme->renderLayout('admin', $data);
}

/**
 * Mostrar logs con filtros
 */
function showLogs($reportLog, $theme, $isAdmin, $userId) {
    $filters = [];

    // Si no es admin, solo ver sus propios logs
    if (!$isAdmin) {
        $filters['userid'] = $userId;
    } else {
        // Aplicar filtros del formulario
        if (!empty($_GET['component'])) {
            $filters['component'] = $_GET['component'];
        }
        if (!empty($_GET['severity'])) {
            $filters['severity'] = (int)$_GET['severity'];
        }
        if (!empty($_GET['userid'])) {
            $filters['userid'] = (int)$_GET['userid'];
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = strtotime($_GET['date_from']);
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = strtotime($_GET['date_to'] . ' 23:59:59');
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
    }

    $page = (int)($_GET['page'] ?? 1);
    $perPage = 50;

    $result = $reportLog->getLogs($filters, $page, $perPage);

    $data = [
        'page_title' => 'Logs del Sistema',
        'logs' => $result['logs'],
        'pagination' => $result,
        'filters' => $filters,
        'is_admin' => $isAdmin
    ];

    echo $theme->renderLayout('admin', $data);
}

/**
 * Mostrar alertas de seguridad
 */
function showSecurityAlerts($logManager, $theme, $isAdmin) {
    if (!$isAdmin) {
        http_response_code(403);
        die('Acceso denegado');
    }

    $filters = [];
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    if (!empty($_GET['severity'])) {
        $filters['severity'] = (int)$_GET['severity'];
    }

    $page = (int)($_GET['page'] ?? 1);
    $result = $logManager->getSecurityAlerts($filters, $page, 50);

    $data = [
        'page_title' => 'Alertas de Seguridad',
        'alerts' => $result['alerts'],
        'pagination' => $result,
        'filters' => $filters
    ];

    echo $theme->renderLayout('admin', $data);
}

/**
 * Manejar exportación
 */
function handleExport($exporter, $reportLog, $logManager) {
    $format = $_GET['format'] ?? 'csv';
    $type = $_GET['type'] ?? 'logs';

    // Aplicar filtros
    $filters = [];
    if (!empty($_GET['component'])) $filters['component'] = $_GET['component'];
    if (!empty($_GET['severity'])) $filters['severity'] = (int)$_GET['severity'];
    if (!empty($_GET['date_from'])) $filters['date_from'] = strtotime($_GET['date_from']);
    if (!empty($_GET['date_to'])) $filters['date_to'] = strtotime($_GET['date_to']);

    try {
        if ($type === 'alerts') {
            $filepath = $exporter->exportAlertsToCSV($filters);
            $exporter->downloadFile($filepath, 'text/csv');
        } elseif ($format === 'json') {
            $filepath = $exporter->exportToJSON($filters);
            $exporter->downloadFile($filepath, 'application/json');
        } elseif ($format === 'html') {
            $html = $exporter->generateHTMLReport($filters);
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
        } else {
            $filepath = $exporter->exportToCSV($filters);
            $exporter->downloadFile($filepath, 'text/csv');
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    exit;
}

/**
 * Obtener etiqueta de severidad
 */
function getSeverityLabel(int $severity): string {
    return match($severity) {
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
        3 => 'Critical',
        default => 'Unknown'
    };
}
