<?php
/**
 * ISER Authentication System - Sistema de Reportes
 *
 * Panel de reportes, logs y auditoría del sistema
 *
 * @package    ISER
 * @category   Report
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 8
 */

// Define base directory
define('ISER_BASE_DIR', dirname(__DIR__, 2));

// Load Composer autoloader
require_once ISER_BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\Core\Config\ConfigManager;
use ISER\Core\Config\SettingsManager;
use ISER\Core\Middleware\AuthMiddleware;
use ISER\Core\Middleware\AdminMiddleware;
use ISER\Report\Log\ReportLog;
use ISER\Report\Log\LogManager;
use ISER\Report\Log\LogExporter;
use ISER\Theme\Iser\ThemeIser;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Initialize the system
$app = new Bootstrap(ISER_BASE_DIR);
$app->init();

// Error handling
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("Report Error: [$errno] $errstr in $errfile:$errline");
    return true;
});

try {
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
    $logDir = ISER_BASE_DIR . '/var/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logger->pushHandler(new StreamHandler($logDir . '/report.log', Logger::DEBUG));

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
            handleExport($exporter, $reportLog, $logManager, $isAdmin);
            break;

        case 'ajax':
            handleAjax($reportLog, $logManager, $isAdmin, $userId);
            break;

        case 'dashboard':
        default:
            showDashboard($reportLog, $logManager, $theme, $isAdmin, $userId);
            break;
    }

} catch (\Exception $e) {
    error_log("Report System Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error interno del sistema',
        'details' => $e->getMessage()
    ]);
    exit;
}

/**
 * Mostrar dashboard de reportes
 */
function showDashboard($reportLog, $logManager, $theme, $isAdmin, $userId): void
{
    try {
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
            $alerts = $alertResult['alerts'] ?? [];
        }

        // Preparar datos para Chart.js
        $chartData = [
            'by_severity' => [],
            'by_component' => [],
            'daily_activity' => []
        ];

        foreach (($stats['by_severity'] ?? []) as $item) {
            $chartData['by_severity'][] = [
                'label' => getSeverityLabel($item['severity']),
                'value' => $item['count']
            ];
        }

        foreach (($stats['by_component'] ?? []) as $item) {
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

        // Renderizar template simple si no existe el tema completo
        renderSimpleDashboard([
            'page_title' => 'Dashboard de Reportes',
            'stats' => $stats,
            'recent_logs' => $recentLogs['logs'] ?? [],
            'alerts' => $alerts,
            'chart_data' => $chartData,
            'is_admin' => $isAdmin
        ]);

    } catch (\Exception $e) {
        error_log("Dashboard Error: " . $e->getMessage());
        echo "<h1>Error al cargar el dashboard</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

/**
 * Mostrar logs con filtros
 */
function showLogs($reportLog, $theme, $isAdmin, $userId): void
{
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

    renderSimpleLogsPage([
        'page_title' => 'Logs del Sistema',
        'logs' => $result['logs'] ?? [],
        'pagination' => $result,
        'filters' => $filters,
        'is_admin' => $isAdmin
    ]);
}

/**
 * Mostrar alertas de seguridad
 */
function showSecurityAlerts($logManager, $theme, $isAdmin): void
{
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

    renderSimpleAlertsPage([
        'page_title' => 'Alertas de Seguridad',
        'alerts' => $result['alerts'] ?? [],
        'pagination' => $result,
        'filters' => $filters
    ]);
}

/**
 * Manejar exportación
 */
function handleExport($exporter, $reportLog, $logManager, $isAdmin): void
{
    if (!$isAdmin) {
        http_response_code(403);
        die('Acceso denegado');
    }

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
 * Manejar peticiones AJAX
 */
function handleAjax($reportLog, $logManager, $isAdmin, $userId): void
{
    header('Content-Type: application/json');

    $filters = $isAdmin ? [] : ['userid' => $userId];
    $filters['date_from'] = strtotime('-30 days');

    $stats = $reportLog->getStatistics($filters);

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => time()
    ]);

    exit;
}

/**
 * Renderizar dashboard simple
 */
function renderSimpleDashboard(array $data): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($data['page_title']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    </head>
    <body>
        <div class="container-fluid py-4">
            <h1 class="mb-4"><?= htmlspecialchars($data['page_title']) ?></h1>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Eventos Totales</h5>
                            <p class="display-6"><?= number_format(count($data['recent_logs'])) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Componentes</h5>
                            <p class="display-6"><?= count($data['stats']['by_component'] ?? []) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Alertas</h5>
                            <p class="display-6"><?= count($data['alerts']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <canvas id="severity-chart" data-chart-data='<?= json_encode($data['chart_data']['by_severity']) ?>'></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <canvas id="component-chart" data-chart-data='<?= json_encode($data['chart_data']['by_component']) ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Actividad Reciente</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="?action=logs" class="btn btn-primary">Ver Todos los Logs</a>
                        <?php if ($data['is_admin']): ?>
                            <a href="?action=security" class="btn btn-warning">Alertas de Seguridad</a>
                            <a href="?action=export&format=csv" class="btn btn-success">Exportar CSV</a>
                        <?php endif; ?>
                    </div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Evento</th>
                                <th>Componente</th>
                                <th>Usuario</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recent_logs'] as $log): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', $log['timecreated']) ?></td>
                                <td><?= htmlspecialchars($log['eventname']) ?></td>
                                <td><?= htmlspecialchars($log['component']) ?></td>
                                <td><?= $log['userid'] ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script src="assets/js/reports.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

/**
 * Renderizar página de logs simple
 */
function renderSimpleLogsPage(array $data): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($data['page_title']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container-fluid py-4">
            <h1 class="mb-4"><?= htmlspecialchars($data['page_title']) ?></h1>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <input type="hidden" name="action" value="logs">
                        <?php if ($data['is_admin']): ?>
                        <div class="col-md-3">
                            <label class="form-label">Componente</label>
                            <input type="text" name="component" class="form-control" value="<?= htmlspecialchars($data['filters']['component'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" name="date_from" class="form-control" value="<?= !empty($data['filters']['date_from']) ? date('Y-m-d', $data['filters']['date_from']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" name="date_to" class="form-control" value="<?= !empty($data['filters']['date_to']) ? date('Y-m-d', $data['filters']['date_to']) : '' ?>">
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Evento</th>
                                <th>Componente</th>
                                <th>Usuario</th>
                                <th>IP</th>
                                <th>Severidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['logs'] as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= date('Y-m-d H:i:s', $log['timecreated']) ?></td>
                                <td><?= htmlspecialchars($log['eventname']) ?></td>
                                <td><?= htmlspecialchars($log['component']) ?></td>
                                <td><?= $log['userid'] ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><?= getSeverityLabel($log['severity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (($data['pagination']['total_pages'] ?? 1) > 1): ?>
                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $data['pagination']['total_pages']; $i++): ?>
                                <li class="page-item <?= $i === $data['pagination']['page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?action=logs&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <a href="?action=dashboard" class="btn btn-secondary">Volver al Dashboard</a>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

/**
 * Renderizar página de alertas simple
 */
function renderSimpleAlertsPage(array $data): void
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($data['page_title']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container-fluid py-4">
            <h1 class="mb-4"><?= htmlspecialchars($data['page_title']) ?></h1>

            <div class="card">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Severidad</th>
                                <th>Título</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['alerts'] as $alert): ?>
                            <tr>
                                <td><?= $alert['id'] ?></td>
                                <td><?= htmlspecialchars($alert['alert_type']) ?></td>
                                <td><?= getSeverityLabel($alert['severity']) ?></td>
                                <td><?= htmlspecialchars($alert['title']) ?></td>
                                <td><span class="badge bg-<?= getStatusBadge($alert['status']) ?>"><?= htmlspecialchars($alert['status']) ?></span></td>
                                <td><?= date('Y-m-d H:i:s', $alert['timecreated']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <a href="?action=dashboard" class="btn btn-secondary">Volver al Dashboard</a>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

/**
 * Obtener etiqueta de severidad
 */
function getSeverityLabel(int $severity): string
{
    return match($severity) {
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
        3 => 'Critical',
        4 => 'Critical',
        default => 'Unknown'
    };
}

/**
 * Obtener clase de badge para estado
 */
function getStatusBadge(string $status): string
{
    return match($status) {
        'new' => 'danger',
        'investigating' => 'warning',
        'resolved' => 'success',
        'false_positive' => 'secondary',
        default => 'info'
    };
}
