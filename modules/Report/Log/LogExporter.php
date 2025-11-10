<?php
/**
 * Exportador de logs a múltiples formatos
 * @package Report\Log
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Report\Log;

use ISER\Core\Database\Database;
use Monolog\Logger;

class LogExporter
{
    private Database $db;
    private Logger $logger;
    private ReportLog $reportLog;

    public function __construct(Database $db, Logger $logger, ReportLog $reportLog)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->reportLog = $reportLog;
    }

    /**
     * Exportar logs a CSV
     */
    public function exportToCSV(array $filters = [], string $filename = null): string
    {
        if (!$filename) {
            $filename = 'logs_' . date('Y-m-d_H-i-s') . '.csv';
        }

        $batchSize = $this->reportLog->getReportConfig('export_batch_size') ?? 1000;
        $page = 1;
        $output = fopen('php://temp', 'r+');

        // Encabezados
        fputcsv($output, [
            'ID', 'Fecha', 'Evento', 'Componente', 'Acción', 'Usuario ID',
            'IP', 'Severidad', 'Descripción'
        ]);

        // Exportar por lotes
        do {
            $result = $this->reportLog->getLogs($filters, $page, $batchSize);

            foreach ($result['logs'] as $log) {
                fputcsv($output, [
                    $log['id'],
                    date('Y-m-d H:i:s', $log['timecreated']),
                    $log['eventname'],
                    $log['component'],
                    $log['action'],
                    $log['userid'],
                    $log['ip_address'],
                    $this->getSeverityLabel($log['severity']),
                    $log['description']
                ]);
            }

            $page++;
        } while ($page <= $result['total_pages']);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // Guardar archivo
        $filepath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($filepath, $csv);

        $this->logger->info('Logs exportados a CSV', [
            'filename' => $filename,
            'filters' => $filters,
            'rows' => $result['total']
        ]);

        return $filepath;
    }

    /**
     * Exportar logs a JSON
     */
    public function exportToJSON(array $filters = [], string $filename = null): string
    {
        if (!$filename) {
            $filename = 'logs_' . date('Y-m-d_H-i-s') . '.json';
        }

        $batchSize = $this->reportLog->getReportConfig('export_batch_size') ?? 1000;
        $page = 1;
        $allLogs = [];

        // Recopilar todos los logs
        do {
            $result = $this->reportLog->getLogs($filters, $page, $batchSize);
            $allLogs = array_merge($allLogs, $result['logs']);
            $page++;
        } while ($page <= $result['total_pages']);

        // Formato de exportación
        $export = [
            'export_date' => date('c'),
            'filters' => $filters,
            'total_records' => count($allLogs),
            'logs' => $allLogs
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Guardar archivo
        $filepath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($filepath, $json);

        $this->logger->info('Logs exportados a JSON', [
            'filename' => $filename,
            'filters' => $filters,
            'rows' => count($allLogs)
        ]);

        return $filepath;
    }

    /**
     * Generar reporte HTML
     */
    public function generateHTMLReport(array $filters = []): string
    {
        $result = $this->reportLog->getLogs($filters, 1, 100); // Primeras 100
        $stats = $this->reportLog->getStatistics($filters);

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Logs - <?= date('Y-m-d H:i:s') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; flex: 1; }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; }
        .stat-card .value { font-size: 24px; font-weight: bold; color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .severity-0 { color: #17a2b8; }
        .severity-1 { color: #ffc107; }
        .severity-2 { color: #fd7e14; }
        .severity-3 { color: #dc3545; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Reporte de Logs del Sistema</h1>
    <p><strong>Generado:</strong> <?= date('Y-m-d H:i:s') ?></p>

    <div class="section">
        <h2>Estadísticas Generales</h2>
        <div class="stats">
            <div class="stat-card">
                <h3>Total de Eventos</h3>
                <div class="value"><?= number_format($result['total']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Componentes Activos</h3>
                <div class="value"><?= count($stats['by_component']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Usuarios Únicos</h3>
                <div class="value"><?= count($stats['top_users']) ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Distribución por Severidad</h2>
        <table>
            <tr>
                <th>Severidad</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
            <?php foreach ($stats['by_severity'] as $sev):
                $percentage = ($sev['count'] / $result['total']) * 100;
            ?>
            <tr>
                <td class="severity-<?= $sev['severity'] ?>">
                    <?= $this->getSeverityLabel($sev['severity']) ?>
                </td>
                <td><?= number_format($sev['count']) ?></td>
                <td><?= number_format($percentage, 2) ?>%</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h2>Top Componentes</h2>
        <table>
            <tr>
                <th>Componente</th>
                <th>Eventos</th>
            </tr>
            <?php foreach ($stats['by_component'] as $comp): ?>
            <tr>
                <td><?= htmlspecialchars($comp['component']) ?></td>
                <td><?= number_format($comp['count']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h2>Eventos Recientes (últimos 100)</h2>
        <table>
            <tr>
                <th>Fecha</th>
                <th>Evento</th>
                <th>Componente</th>
                <th>Usuario</th>
                <th>IP</th>
                <th>Severidad</th>
            </tr>
            <?php foreach ($result['logs'] as $log): ?>
            <tr>
                <td><?= date('Y-m-d H:i:s', $log['timecreated']) ?></td>
                <td><?= htmlspecialchars($log['eventname']) ?></td>
                <td><?= htmlspecialchars($log['component']) ?></td>
                <td><?= $log['userid'] ?></td>
                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                <td class="severity-<?= $log['severity'] ?>">
                    <?= $this->getSeverityLabel($log['severity']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="footer">
        <p>ISER Authentication System - Sistema de Reportes y Auditoría</p>
        <p>Este reporte fue generado automáticamente el <?= date('Y-m-d H:i:s') ?></p>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Exportar alertas de seguridad a CSV
     */
    public function exportAlertsToCSV(array $filters = [], string $filename = null): string
    {
        if (!$filename) {
            $filename = 'security_alerts_' . date('Y-m-d_H-i-s') . '.csv';
        }

        $logManager = new LogManager($this->db, $this->logger, $this->reportLog);
        $result = $logManager->getSecurityAlerts($filters, 1, 10000);

        $output = fopen('php://temp', 'r+');

        // Encabezados
        fputcsv($output, [
            'ID', 'Tipo', 'Severidad', 'Título', 'Descripción', 'Estado',
            'Usuario', 'IP', 'Fecha Creación', 'Fecha Resolución'
        ]);

        foreach ($result['alerts'] as $alert) {
            fputcsv($output, [
                $alert['id'],
                $alert['alert_type'],
                $this->getSeverityLabel($alert['severity']),
                $alert['title'],
                $alert['description'],
                $alert['status'],
                $alert['userid'] ?? 'N/A',
                $alert['ip_address'] ?? 'N/A',
                date('Y-m-d H:i:s', $alert['timecreated']),
                $alert['timemodified'] ? date('Y-m-d H:i:s', $alert['timemodified']) : 'N/A'
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filepath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($filepath, $csv);

        return $filepath;
    }

    /**
     * Descargar archivo
     */
    public function downloadFile(string $filepath, string $contentType = 'text/csv'): void
    {
        if (!file_exists($filepath)) {
            throw new \RuntimeException("Archivo no encontrado: {$filepath}");
        }

        $filename = basename($filepath);

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($filepath);

        // Limpiar archivo temporal
        @unlink($filepath);
    }

    /**
     * Obtener etiqueta de severidad
     */
    private function getSeverityLabel(int $severity): string
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
}
