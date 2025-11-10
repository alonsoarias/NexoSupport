<?php
/**
 * Clase principal para gestión de logs y reportes
 * @package Report\Log
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Report\Log;

use ISER\Core\Database\Database;
use Monolog\Logger;
use PDO;

class ReportLog
{
    private Database $db;
    private Logger $logger;

    /**
     * Severidad de logs
     */
    public const SEVERITY_INFO = 0;
    public const SEVERITY_WARNING = 1;
    public const SEVERITY_ERROR = 2;
    public const SEVERITY_CRITICAL = 3;

    /**
     * Operaciones CRUD
     */
    public const CRUD_CREATE = 'c';
    public const CRUD_READ = 'r';
    public const CRUD_UPDATE = 'u';
    public const CRUD_DELETE = 'd';

    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Registrar un evento en el sistema
     */
    public function log(array $data): bool
    {
        try {
            $required = ['eventname', 'component', 'action', 'crud'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Campo requerido: {$field}");
                }
            }

            $params = [
                'eventname' => $data['eventname'],
                'component' => $data['component'],
                'action' => $data['action'],
                'target' => $data['target'] ?? null,
                'objecttable' => $data['objecttable'] ?? null,
                'objectid' => $data['objectid'] ?? null,
                'crud' => $data['crud'],
                'userid' => $data['userid'] ?? ($_SESSION['user_id'] ?? 0),
                'relateduserid' => $data['relateduserid'] ?? null,
                'ip_address' => $data['ip_address'] ?? $this->getClientIp(),
                'user_agent' => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                'context' => isset($data['context']) ? json_encode($data['context']) : null,
                'description' => $data['description'] ?? null,
                'severity' => $data['severity'] ?? self::SEVERITY_INFO,
                'timecreated' => $data['timecreated'] ?? time()
            ];

            $this->db->execute(
                "INSERT INTO iser_logs
                (eventname, component, action, target, objecttable, objectid, crud,
                 userid, relateduserid, ip_address, user_agent, context, description,
                 severity, timecreated)
                VALUES
                (:eventname, :component, :action, :target, :objecttable, :objectid, :crud,
                 :userid, :relateduserid, :ip_address, :user_agent, :context, :description,
                 :severity, :timecreated)",
                $params
            );

            // Actualizar estadísticas diarias
            $this->updateDailyStats($params['component'], $params['timecreated']);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error al registrar log: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Obtener logs con filtros y paginación
     */
    public function getLogs(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        // Filtros disponibles
        if (!empty($filters['component'])) {
            $where[] = 'component = :component';
            $params['component'] = $filters['component'];
        }

        if (!empty($filters['userid'])) {
            $where[] = 'userid = :userid';
            $params['userid'] = $filters['userid'];
        }

        if (!empty($filters['severity'])) {
            $where[] = 'severity = :severity';
            $params['severity'] = $filters['severity'];
        }

        if (!empty($filters['eventname'])) {
            $where[] = 'eventname LIKE :eventname';
            $params['eventname'] = '%' . $filters['eventname'] . '%';
        }

        if (!empty($filters['ip_address'])) {
            $where[] = 'ip_address = :ip_address';
            $params['ip_address'] = $filters['ip_address'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'timecreated >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'timecreated <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(eventname LIKE :search OR description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_logs {$whereClause}",
            $params
        )[0]['count'] ?? 0;

        // Obtener registros
        $logs = $this->db->query(
            "SELECT * FROM iser_logs
             {$whereClause}
             ORDER BY timecreated DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [
                'limit' => $perPage,
                'offset' => $offset
            ])
        );

        // Decodificar context JSON
        foreach ($logs as &$log) {
            if ($log['context']) {
                $log['context'] = json_decode($log['context'], true);
            }
        }

        return [
            'logs' => $logs,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener log específico por ID
     */
    public function getLogById(int $id): ?array
    {
        $result = $this->db->query(
            "SELECT * FROM iser_logs WHERE id = :id",
            ['id' => $id]
        );

        if (empty($result)) {
            return null;
        }

        $log = $result[0];
        if ($log['context']) {
            $log['context'] = json_decode($log['context'], true);
        }

        return $log;
    }

    /**
     * Obtener estadísticas de logs
     */
    public function getStatistics(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'timecreated >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'timecreated <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Estadísticas por severidad
        $bySeverity = $this->db->query(
            "SELECT severity, COUNT(*) as count
             FROM iser_logs {$whereClause}
             GROUP BY severity
             ORDER BY severity",
            $params
        );

        // Estadísticas por componente
        $byComponent = $this->db->query(
            "SELECT component, COUNT(*) as count
             FROM iser_logs {$whereClause}
             GROUP BY component
             ORDER BY count DESC
             LIMIT 10",
            $params
        );

        // Estadísticas por operación CRUD
        $byCrud = $this->db->query(
            "SELECT crud, COUNT(*) as count
             FROM iser_logs {$whereClause}
             GROUP BY crud",
            $params
        );

        // Actividad por hora
        $byHour = $this->db->query(
            "SELECT FROM_UNIXTIME(timecreated, '%H') as hour, COUNT(*) as count
             FROM iser_logs {$whereClause}
             GROUP BY hour
             ORDER BY hour",
            $params
        );

        // Top usuarios más activos
        $topUsers = $this->db->query(
            "SELECT userid, COUNT(*) as count
             FROM iser_logs {$whereClause}
             GROUP BY userid
             ORDER BY count DESC
             LIMIT 10",
            $params
        );

        return [
            'by_severity' => $bySeverity,
            'by_component' => $byComponent,
            'by_crud' => $byCrud,
            'by_hour' => $byHour,
            'top_users' => $topUsers
        ];
    }

    /**
     * Actualizar estadísticas diarias
     */
    private function updateDailyStats(string $component, int $timestamp): void
    {
        $date = date('Y-m-d', $timestamp);

        $this->db->execute(
            "INSERT INTO iser_logs_daily
             (stat_type, stat_value, stat_date, component, timecreated)
             VALUES ('event_count', 1, :date, :component, :time)
             ON DUPLICATE KEY UPDATE
             stat_value = stat_value + 1",
            [
                'date' => $date,
                'component' => $component,
                'time' => time()
            ]
        );
    }

    /**
     * Obtener estadísticas diarias
     */
    public function getDailyStats(string $dateFrom, string $dateTo, ?string $component = null): array
    {
        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        $where = 'stat_date BETWEEN :date_from AND :date_to';

        if ($component) {
            $where .= ' AND component = :component';
            $params['component'] = $component;
        }

        return $this->db->query(
            "SELECT * FROM iser_logs_daily
             WHERE {$where}
             ORDER BY stat_date DESC",
            $params
        );
    }

    /**
     * Limpiar logs antiguos según configuración
     */
    public function cleanupOldLogs(): int
    {
        $config = $this->db->query(
            "SELECT config_value FROM iser_report_config
             WHERE config_name = 'log_retention_days'"
        );

        if (empty($config)) {
            return 0;
        }

        $retentionDays = (int)$config[0]['config_value'];
        $cutoffTime = time() - ($retentionDays * 86400);

        $result = $this->db->execute(
            "DELETE FROM iser_logs WHERE timecreated < :cutoff",
            ['cutoff' => $cutoffTime]
        );

        $this->logger->info("Logs antiguos limpiados", [
            'retention_days' => $retentionDays,
            'rows_deleted' => $result
        ]);

        return $result;
    }

    /**
     * Obtener IP del cliente
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs (proxy chain), tomar la primera
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Registrar inicio de sesión
     */
    public function logLogin(int $userId, bool $success, ?string $failureReason = null): void
    {
        // Registrar en tabla principal
        $this->log([
            'eventname' => $success ? 'user_login_success' : 'user_login_failed',
            'component' => 'auth',
            'action' => 'login',
            'crud' => self::CRUD_READ,
            'userid' => $success ? $userId : 0,
            'severity' => $success ? self::SEVERITY_INFO : self::SEVERITY_WARNING,
            'description' => $failureReason
        ]);

        // Registrar en tabla de intentos de login
        $this->db->execute(
            "INSERT INTO iser_login_attempts
             (username, ip_address, user_agent, success, failure_reason, userid, timecreated)
             VALUES (:username, :ip, :ua, :success, :reason, :userid, :time)",
            [
                'username' => $_POST['username'] ?? 'unknown',
                'ip' => $this->getClientIp(),
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'success' => $success ? 1 : 0,
                'reason' => $failureReason,
                'userid' => $success ? $userId : null,
                'time' => time()
            ]
        );
    }

    /**
     * Obtener intentos de login fallidos recientes
     */
    public function getFailedLoginAttempts(?string $ipAddress = null, ?string $username = null, int $hours = 24): array
    {
        $where = ['success = 0', 'timecreated > :cutoff'];
        $params = ['cutoff' => time() - ($hours * 3600)];

        if ($ipAddress) {
            $where[] = 'ip_address = :ip';
            $params['ip'] = $ipAddress;
        }

        if ($username) {
            $where[] = 'username = :username';
            $params['username'] = $username;
        }

        return $this->db->query(
            "SELECT * FROM iser_login_attempts
             WHERE " . implode(' AND ', $where) . "
             ORDER BY timecreated DESC",
            $params
        );
    }

    /**
     * Obtener configuración de reportes
     */
    public function getReportConfig(string $configName): mixed
    {
        $result = $this->db->query(
            "SELECT config_value, config_type FROM iser_report_config
             WHERE config_name = :name",
            ['name' => $configName]
        );

        if (empty($result)) {
            return null;
        }

        $value = $result[0]['config_value'];
        $type = $result[0]['config_type'];

        return match($type) {
            'int' => (int)$value,
            'bool' => (bool)$value,
            'json' => json_decode($value, true),
            default => $value
        };
    }
}
